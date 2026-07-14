# Weekly Operations Review

**Cadence:** Mondays 09:00 WAT  
**Owners:** Platform on-call lead, Commerce on-call, Support lead  
**Traceability:** SCP-OPS-001-02, SCP-OPS-001-05, SCP-OPS-001-07

## Inputs

- `ops:error-budget-report --json`
- Synthetic checkout probe results
- Webhook delivery failures and dead letters
- Open SEV1/SEV2 incidents and postmortem action items
- Support ticket SLA and top issue volume
- Capacity indicators: DB connections, queue depth, storage growth

## Review Checklist

- [ ] Error budget state recorded; feature freeze decision made when policy requires it
- [ ] Incidents reviewed; SEV1/SEV2 postmortem actions have owners
- [ ] Webhook backlog and dead letters checked
- [ ] Support macros/articles updated for repeated issues
- [ ] Capacity risks logged with owner and due date

## Output

Create an `OPS-YYYY-WW` note with decisions, risks, and follow-up actions.
