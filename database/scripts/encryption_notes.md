# Eloquent Encrypted Casts & Blind Indexes

This document explains why direct queries on encrypted columns fail and how deterministic blind indexes allow secure database lookups.

---

## 1. Why Direct Queries on Encrypted Columns Fail

When a column is cast to `'encrypted'` or `'encrypted:array'`, Laravel uses symmetric encryption (AES-256-CBC or AES-128-CBC) to encrypt the data before writing it to the database.

A typical encrypted database entry looks like this:
```json
{
  "iv": "tfMV4NJoupFCP+z9ZPV/kw==",
  "value": "d/41ddY0uEi5lDK4RF7L4A==",
  "mac": "79f6d1724757faf0b35a4a38106c0749916f980fbd227f30a3a53e628c51d20d",
  "tag": ""
}
```

### Key Reasons:
1. **Randomized Initialization Vector (IV)**:
   - To prevent attackers from discovering patterns in the encrypted data, symmetric encryption uses a random IV for every encryption operation.
   - Encrypting the value `"1234567890"` three times yields three completely different ciphertexts.
2. **Text Mismatch**:
   - The query `User::where('phone', '1234567890')->first()` attempts to find a database row where the `phone` column matches the literal string `'1234567890'`.
   - Since the database stores the encrypted base64 payload, the query finds zero results.
3. **No Database-Level Decryption**:
   - The database engine itself has no access to the application's `APP_KEY`. It cannot decrypt the data on the fly to perform the comparison.

---

## 2. The Solution: Deterministic Blind Indexes

A **blind index** is a secure, deterministic one-way hash of the plain text value stored in a separate column.

### Implementation:
1. **Salted Hashing**:
   - We hash the plaintext value using a strong hashing function (HMAC-SHA256) combined with a secret salt/key (`BLIND_INDEX_SECRET`):
     ```php
     $blindIndex = hash_hmac('sha256', $plainText, env('BLIND_INDEX_SECRET'));
     ```
2. **Database Schema**:
   - We store the hash in a dedicated column, e.g., `phone_blind_index`, which is indexed.
3. **Lookups**:
   - To query by phone, we calculate the hash of the search term in the application, and run the query against the blind index column:
     ```php
     $searchHash = hash_hmac('sha256', $searchPhone, env('BLIND_INDEX_SECRET'));
     $user = User::where('phone_blind_index', $searchHash)->first();
     ```
4. **Security**:
   - Because the hash is one-way (SHA-256), a database leak does not expose the user's phone number.
   - Because it uses a unique salt/secret, rainbow tables or dictionary attacks are useless without access to the secret key.

---

## 3. Order Model Shipping Phone Blind Index

Similar to user phone number, the `Order` model casts the `shipping_phone` attribute to `encrypted` and maintains a `shipping_phone_blind_index` column for lookups:

- **Saving Hook**: Calculates the HMAC-SHA256 of `shipping_phone` Deterministically on order save.
- **Dynamic Scope**: `Order::whereShippingPhone($phone)->first()` provides clean access utilizing the blind index.
