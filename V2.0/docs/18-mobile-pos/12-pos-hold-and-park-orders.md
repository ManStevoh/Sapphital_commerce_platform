# Chapter 12: POS Hold & Park Orders

**Document ID:** SCP-MOB-001-12  
**Version:** 1.0.0  
**Status:** ✅ Active  
**Traceability:** Vol 18 Ch. 05, FR-POS-001  
**Legacy mapping:** `Modules/Pos` hold orders

---

## Purpose

**Park/hold** in-progress POS carts — cashier serves another customer and resumes later.

## Scope

- Hold order with label (e.g. "Table 4", "Blue shirt customer")
- List held orders on register
- Resume → active cart → checkout
- Expire held carts after configurable hours

## Out of Scope

- Table management restaurant module (Phase 3 vertical)

---

## 1. Entity

**PosHeldCart** — `id`, `tenant_id`, `register_id`, `label`, `cart_snapshot_json`, `held_by_user_id`, `held_at`, `expires_at`

---

## 2. Flow

```text
Cashier → Hold → Enter label → Cart cleared for new sale
Cashier → Held orders → Resume → Cart restored
```

Offline: held carts stored in SQLite on device; sync on reconnect.

---

## 3. Rules

| Rule | Description |
|------|-------------|
| BR-HOLD-001 | Max 20 held carts per register |
| BR-HOLD-002 | Expired holds delete snapshot after 24h default |
| BR-HOLD-003 | Resume validates inventory still available |

---

## 4. Acceptance Criteria

- [ ] Hold and resume preserves line items and discounts
- [ ] Two registers do not share held carts unless `store_shared_holds=true`
- [ ] Offline hold works without server round-trip

---

## References

- [Ch. 05 — POS Architecture](./05-pos-architecture.md)
