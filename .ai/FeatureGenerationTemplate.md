# 🧱 Shopware Feature Definition Template (for Cursor)

## 1. Feature Name
> What is the name of your module?

**Example:**  
`Advanced Product Availability Rules`

---

## 2. High-Level Description
> What is this module for? What problem does it solve? Who uses it?

**Example:**  
This module allows merchants to define complex product availability rules based on customer group, time of day, inventory status, and custom fields. It integrates with the storefront, admin UI, and API.

---

## 3. Key Use Cases
> List 2–5 real-world usage scenarios that this module supports.

**Example:**
- Hide out-of-stock products during weekends
- Allow wholesale customers to pre-order upcoming products
- Block visibility of certain products to guest users

---

## 4. Data Model Design
> Describe the entities involved in your module, their fields, and relationships. Use pseudo-code or Shopware schema notation.

**Example:**
```php
Entity: ProductAvailabilityRule
Fields:
- id (UUID)
- name (string)
- conditions (JSON)
- active (bool)

Relation:
- ManyToOne → Product
```
--- 

## 5. Integration Points
> Where does this module hook into Shopware? Describe events, services, UI components, and so on.

Example:

- Product listing API extension (filters out restricted products)

- Admin module to manage rules (Vue.js extension in Administration)

- Event subscriber for ProductLoadedEvent

- Custom CMS block to highlight restricted products

## 6. Storefront Behavior
> Describe how this module affects the storefront and what the user experience is.

**Example:**

- Products that fail rules should be hidden from listings

- PDP should show a “not available” message if rule blocks it

- Logged-in state may affect rule resolution


## 7. Admin/Backoffice UX
> Describe what merchants or admins will see or do in the administration interface.

**Example:**

- Custom module under Catalog for managing availability rules

- Modal form to add/edit rule with condition builder

- Status toggle, sortable table, searchable rule list


## 8. API Behavior
> What changes, if any, does this module make to public or internal APIs?

**Example:**

- /product endpoint respects availability rules

- /availability-rules CRUD endpoints (admin only)

## 9. Shopware Version & Tech Constraints
> What versions and technologies are required or targeted?

**Example:**

Built for Shopware 6.5.7+

Symfony 6, PHP 8.2

Uses Store API only

Plugin base namespace: Torq\ProductAvailability


## 10. Design Goals / Notes for Cursor
> Be specific about what you want Cursor to generate or focus on first.

**Example:**
Start by scaffolding the entity definition for ProductAvailabilityRule with a migration, repository, and admin listing component. Use best practices for Shopware entity extensions and administration integration.

## 11. Test Criteria & Acceptance Conditions
> Define what conditions must be met for this module to be considered correct and complete. Include edge cases, test scenarios, and any performance or UX expectations.

**Functional Test Cases (Examples):**
- ✅ Rule applies correctly to hide a product from the storefront listing
- ✅ Product detail page shows "not available" message when blocked by a rule
- ✅ Rule management UI allows create, edit, delete of rules
- ✅ Conditions JSON validates before saving
- ✅ Rule only applies when marked `active = true`

**Edge Cases:**
- ✅ Rule with invalid JSON condition is skipped and logged
- ✅ Guest user sees a different result than a logged-in customer due to rule targeting
- ✅ Rule applying to out-of-stock products works with real-time stock updates

**Integration Checks:**
- ✅ API `/product` responses reflect rule filtering
- ✅ Rule entity appears in admin search
- ✅ Event subscriber for `ProductLoadedEvent` is triggered and applies rules

**Performance & Stability:**
- ✅ No perceptible delay on category listing page with 100+ rules defined
- ✅ No unhandled exceptions in rule evaluation service
- ✅ Rule engine caches correctly per user session when applicable

**Test Coverage Goal:**
- Aim for unit + integration test coverage of all rule evaluation logic and admin module actions.

