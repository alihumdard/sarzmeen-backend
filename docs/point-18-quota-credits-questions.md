# Point 18 — "Quota & Credits": questions before we build

Written for the client. Our reading of the requirement, what the system
already does, and the decisions we need from you before we start.

---

## Where things stand today

Subscription plans **already store three limits**, and an admin can already
edit them under Subscription Plans:

| Field | Meaning | Default |
|---|---|---|
| `listing_limit` | how many properties the account may list | 5 |
| `featured_limit` | how many of those may be featured | 0 |
| `image_limit` | how many images per property | 10 |

**None of these three limits is currently enforced.** They are saved,
editable and shown on the pricing page, but nothing checks them when a
property is created, featured, or when images are uploaded. An account on a
5-listing plan can add any number of listings right now.

What *is* enforced today is only whether a subscription exists at all, plus
two on/off features (`analytics_access`, `priority_support`).

So most of point 18 is **enforcing quotas that already exist**. "Credits"
is the part that does not exist in any form yet, and that is where we most
need your direction.

---

## Questions

### 1. What should a quota count?

Please confirm which of these are capped, and whether the cap is a running
total or a per-period allowance:

- [ ] **Active listings** — e.g. "10 live properties at a time"
  (a running total: deleting one frees a slot)
- [ ] **Listings created per period** — e.g. "10 new properties a month"
  (an allowance: deleting one does *not* free a slot)
- [ ] **Featured listings** — already a field; same question applies
- [ ] **Images per property** — already a field
- [ ] **Something else?** (project listings, inquiries received, team
  members an agency may add)

The difference matters: a running total and a monthly allowance behave very
differently for an agent who edits their listings often.

### 2. When does a quota reset?

- [ ] On the subscription's own renewal date (plans have `duration_days`)
- [ ] On a fixed calendar day (1st of each month, same for everyone)
- [ ] Never — the quota is a standing ceiling, not a periodic allowance

### 3. What happens when the quota runs out?

- [ ] Blocked with a clear message and a link to upgrade
- [ ] Allowed, but the listing stays unpublished until the admin approves
- [ ] Allowed, and the extra is charged (this needs credits — see below)

And for accounts that are **already over** a new, lower limit when you
change a plan: do their existing listings stay live, or get unpublished?
We would suggest they stay live and only *new* ones are blocked, but this
is your call.

### 4. What is a "credit", exactly?

This is the part we cannot infer. Which of these do you mean?

- **(a) A top-up on a quota** — an agent out of listing slots buys 5 more.
  One credit = one listing. Credits sit alongside the plan quota and are
  spent after it runs out.
- **(b) A separate currency** — the account holds a credit balance, and
  different actions cost different amounts (listing = 1, featuring = 5,
  boosting to the top = 10).
- **(c) A goodwill allowance** — the admin grants credits by hand to settle
  a complaint or as a promotion, and they are not for sale.
- **(d) Some combination.**

### 5. How does an account get credits?

- [ ] Included with a plan each period
- [ ] Bought separately (if so: do they expire, and what do they cost?)
- [ ] Granted by an admin by hand
- [ ] All of the above

### 6. Who can see and adjust what?

- Should an **agency** hold one shared quota for its whole team, or does
  each agent have their own? (Agencies can add team members, so this needs
  deciding.)
- Should the **admin** be able to override one account's quota without
  changing the plan everyone else is on?
- Should an account see its own usage — "7 of 10 listings used" — and
  where: the dashboard, the add-property page, or both?

### 7. Does anything need a record?

Should we keep a history of credits granted, bought and spent, so a dispute
can be settled later? We would recommend yes, and it is cheap to add at the
start but awkward to backfill afterwards.

---

## What we would suggest, if you want a default

If it is easier to react to a proposal than to answer the list above, we
would build this:

1. **Quota = active listings and featured listings**, enforced from the
   fields that already exist, resetting with the subscription period.
2. **Over quota = blocked**, with the message naming the limit and linking
   to upgrade. Existing listings over a newly lowered limit stay live.
3. **Credits = (a), a top-up on the listing quota.** One credit, one
   listing. Spent only once the plan quota is used up.
4. **Admin can grant credits by hand** and can override one account's
   quota.
5. **Every grant and spend is recorded**, with who did it and why.
6. **Agency quota is shared** across its team, since the agency pays.
7. **Usage shown** on the dashboard and again on the add-property page,
   where it matters.

Tell us which of these to change and we will start from there. We have
flagged the enforcement gap above separately, because whichever way point
18 goes, the limits being unenforced today is worth fixing on its own.
