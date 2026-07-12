# Chapter 12: Runbooks

**Document ID:** SCP-INF-001-12  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** NFR-021 – NFR-028, NFR-076, Volume 14 Operations

---

## Purpose

Provide **operational runbooks** for common and critical SCP infrastructure procedures — written for on-call engineers supporting Nigeria-primary production.

## Scope

- Deployment and rollback
- Database failover and restore
- Redis/queue recovery
- Meilisearch reindex
- SSL/certificate renewal
- Cloudflare incident response
- Octane worker restart
- Capacity emergency scale-up

## Out of Scope

- Application bug fixes (engineering backlog)
- Legal/comms incident templates (Volume 14 Ch. 08)
- Merchant support scripts (Volume 14 Ch. 07)

---

## 1. Runbook Index

| ID | Title | Severity | RTO Target |
|----|-------|----------|------------|
| RB-001 | Production deploy | Routine | — |
| RB-002 | Production rollback | SEV2 | 15 min |
| RB-003 | Database restore from backup | SEV1 | 4 h |
| RB-004 | PostgreSQL failover to replica | SEV1 | 30 min |
| RB-005 | Redis failure / flush recovery | SEV2 | 1 h |
| RB-006 | Horizon queue backlog | SEV2 | 2 h |
| RB-007 | Meilisearch full reindex | SEV3 | 4 h |
| RB-008 | Octane memory leak restart | SEV3 | 15 min |
| RB-009 | Cloudflare WAF false positive | SEV3 | 30 min |
| RB-010 | SSL certificate renewal failure | SEV2 | 1 h |
| RB-011 | Origin unreachable (CF 522) | SEV1 | 30 min |
| RB-012 | Emergency scale-up (traffic spike) | SEV2 | 1 h |

---

## 2. RB-001: Production Deploy

**Preconditions:** CI green; staging validated; change ticket approved.

```bash
# 1. Notify #ops-deploys Slack channel
# 2. Pull image tag from CI artifact
export IMAGE_TAG=sha-abc1234

# 3. Run migrations on standby instance
docker compose -f docker-compose.prod.yml exec octane-standby \
  php artisan migrate --force

# 4. Deploy rolling
docker compose -f docker-compose.prod.yml up -d --no-deps octane storefront

# 5. Verify health
curl -sf https://api.sapphital.com/ready

# 6. Monitor 15 min: error rate, p95 latency, queue lag
```

**Abort:** Error rate > 2× baseline → execute RB-002.

---

## 3. RB-002: Production Rollback

**Trigger:** SEV2+ regression post-deploy.

```bash
export PREVIOUS_TAG=sha-prev5678

# 1. Shift traffic to previous image (all octane instances)
docker compose -f docker-compose.prod.yml set octane image=scp-api:$PREVIOUS_TAG
docker compose -f docker-compose.prod.yml up -d --no-deps octane

# 2. Rollback storefront if needed
docker compose -f docker-compose.prod.yml set storefront image=scp-storefront:$PREVIOUS_TAG
docker compose -f docker-compose.prod.yml up -d --no-deps storefront

# 3. Verify /ready
# 4. If migration was forward-only incompatible: activate compat layer flag
#    FEATURE_ROLLBACK_COMPAT=true in env — restart octane

# 5. Post incident note; schedule forward fix
```

**Target:** Traffic on previous version within **15 minutes**.

---

## 4. RB-003: Database Restore from Backup

**Trigger:** Data corruption, accidental delete, ransomware.

| Step | Action |
|------|--------|
| 1 | Declare SEV1; freeze writes (`MAINTENANCE_MODE=true`) |
| 2 | Identify backup: R2 `backups/postgres/YYYY-MM-DD-HH.dump` |
| 3 | Provision restore VM or stop app tier |
| 4 | `pg_restore --clean --if-exists -d scp_restored backup.dump` |
| 5 | Validate row counts: tenants, orders last 24h |
| 6 | Swap DNS/connection string to restored DB OR point-in-time if WAL |
| 7 | Lift maintenance; monitor 1 h |

**RPO:** 6 hours (backup frequency). **RTO:** 4 hours.

---

## 5. RB-004: PostgreSQL Failover to Replica

**Trigger:** Primary DB unresponsive.

```bash
# 1. Confirm primary down (not network blip)
pg_isready -h postgres-primary -p 5432

# 2. Promote replica
pg_ctl promote -D /var/lib/postgresql/data

# 3. Update PgBouncer target to new primary IP
# 4. Restart PgBouncer
# 5. Restart Octane (clear connection pools)
# 6. Rebuild replica from new primary when stable
```

**Target:** 30 minutes to restored writes.

---

## 6. RB-005: Redis Failure

| Scenario | Action |
|----------|--------|
| Redis down | Restart container; sessions lost → users re-login |
| Memory full | Identify large keys; flush `cache:*` prefix only |
| Corrupt persistence | Start fresh; warm cache from DB |

**Never** `FLUSHALL` in production without SEV1 approval — clears sessions and queues.

---

## 7. RB-006: Horizon Queue Backlog

**Symptoms:** Queue lag p95 > 5 min; delayed emails, webhooks.

```bash
# 1. Check queue depths
php artisan horizon:status

# 2. Scale workers
docker compose up -d --scale horizon=8

# 3. Identify poison job — failed_jobs table
php artisan queue:failed

# 4. If single bad job: retry after fix OR delete
# 5. If webhook storm: enable WEBHOOK_THROTTLE=true

# 6. Scale down after lag normalizes
```

---

## 8. RB-007: Meilisearch Full Reindex

**Trigger:** Index corruption, schema change, tenant index drift.

```bash
# 1. Pause indexer workers
php artisan horizon:pause

# 2. Create new index version
php artisan search:reindex --all --new-index

# 3. Swap alias when complete
php artisan search:swap-index

# 4. Resume horizon
php artisan horizon:continue

# 5. Validate search on 5 sample tenants
```

**Duration:** ~1h per 500k documents. Run off-peak WAT.

---

## 9. RB-008: Octane Memory Leak Restart

**Symptoms:** Worker memory > 200 MB; gradual latency increase.

```bash
# Graceful rolling restart
docker compose restart octane
# OR send SIGUSR1 for graceful worker reload (FrankenPHP)

# Verify max_requests config = 1000
# Check plugin memory profiling if recurring
```

---

## 10. RB-011: Origin Unreachable (Cloudflare 522)

| Check | Command / Action |
|-------|------------------|
| Origin up? | `curl origin-ip:8000/health` from bastion |
| Cloudflare IP allowlist | Verify CF IP ranges in firewall |
| TLS cert valid? | `openssl s_client -connect origin:443` |
| Disk full? | `df -h` on app VM |
| DDoS? | Cloudflare analytics → enable Under Attack mode |

**Comms:** Update status page (Volume 14 Ch. 08) within 10 minutes of SEV1.

---

## 11. RB-012: Emergency Scale-Up

**Trigger:** Traffic 3× normal (viral merchant sale, Black Friday Nigeria).

| Step | Action |
|------|--------|
| 1 | Enable Cloudflare caching aggressive mode for storefront |
| 2 | Scale Octane +4 instances |
| 3 | Scale Horizon +4 workers |
| 4 | Verify PgBouncer pool not saturated |
| 5 | Contact merchant re: rate limits if API abuse |
| 6 | Post-event: scale down within 24h |

---

## 12. On-Call Expectations

| Severity | Response Time | Escalation |
|----------|---------------|------------|
| SEV1 | 15 min | Lead Architect + DPO if data breach |
| SEV2 | 30 min | Platform engineer |
| SEV3 | Next business day | Queue for sprint |

On-call rotation: 1-week shifts; handoff Monday 10:00 WAT.

---

## 13. Acceptance Criteria

- [ ] ≥ 12 runbooks indexed with severity and RTO
- [ ] Deploy and rollback procedures with 15 min rollback target
- [ ] DB restore RPO 6h / RTO 4h documented
- [ ] Redis FLUSHALL restriction stated
- [ ] Meilisearch reindex procedure with alias swap
- [ ] Cloudflare 522 checklist documented
- [ ] On-call response times SEV1–SEV3 defined
- [ ] Nigeria WAT timezone referenced for maintenance windows

---

## References

- [Volume 14 Ch. 03 — Incident Management](../14-operations/03-incident-management.md)
- [Volume 14 Ch. 04 — On-Call](../14-operations/04-on-call-escalation.md)
- [Chapter 09 — Backup & DR](./09-backup-disaster-recovery.md)
- [Volume 3 Ch. 12 — Deployment Topology](../03-architecture/12-deployment-and-runtime-topology.md)
