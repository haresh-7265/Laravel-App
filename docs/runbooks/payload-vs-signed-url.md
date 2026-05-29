# Architectural Comparison: Signed URLs vs. Encrypted Payloads

When transmitting secure, temporary, or stateful links via URLs, Laravel developers have two primary cryptographic mechanisms: **Signed URLs** and **Encrypted Payloads**. Choosing the correct approach depends on data sensitivity and authorization requirements.

---

## 1. Summary Comparison Table

| Attribute | Signed URLs | Encrypted Payloads |
| :--- | :--- | :--- |
| **Mechanism** | Plaintext parameters + HMAC-SHA256 signature | Ciphertext (AES-256-CBC) payload |
| **Data Visibility** | Plaintext (visible to users, proxies, logs) | Encrypted (completely hidden ciphertext) |
| **Tamper Protection** | Yes (HMAC mismatch aborts request) | Yes (invalid/altered ciphertext throws DecryptException) |
| **Authentication Requirement** | Typically requires authenticated session checks | Can facilitate guest access safely (embedded context) |
| **Performance** | Faster (HMAC hashing is extremely fast) | Moderate (symmetric encryption/decryption overhead) |
| **URL Length** | Shorter | Longer (base64 cipher text including IV and MAC) |

---

## 2. Signed URLs (e.g., `URL::temporarySignedRoute`)

### How it Works:
Signed URLs append plaintext query parameters (such as `expires` and `signature`) to the URL. The signature is a cryptographic HMAC-SHA256 hash of the entire URL string using the application's `APP_KEY` as the secret key.
```
https://app.test/orders/1/download-invoice?expires=1716942000&signature=abc123xyz...
```

### When to Use:
1. **Non-Sensitive Route Parameters**:
   - When the URL paths/parameters do not contain PII or confidential information (e.g., verifying a user ID or public product slug).
2. **Authenticated Workflows**:
   - For links meant to be clicked *only* by the logged-in user who owns the resource, where standard authorization middleware (`$this->authorize(...)`) runs on the receiving route.
3. **Email Verification**:
   - Standard Laravel email verification links use Signed URLs because the email recipient's ID is public anyway, and the link just confirms access to that inbox.

---

## 3. Encrypted Payloads (e.g., `Crypt::encryptString`)

### How it Works:
The parameters are encoded (typically via JSON to prevent PHP object injection risks) and encrypted using AES-256-CBC or AES-128-CBC. The resulting encrypted base64 payload is passed as a single query parameter.
```
https://app.test/shared-invoice?payload=eyJpdiI6IlRsV1k5b...",
```

### When to Use:
1. **Sensitive or Hidden Context (PII)**:
   - When parameter details (e.g., emails, transaction amounts, database primary keys) must remain hidden from browser histories, ISP logs, and proxies.
2. **Guest / Public Sharing Links**:
   - When a resource (like an invoice PDF) needs to be temporarily shared with a non-logged-in guest (e.g. an accountant). The encrypted payload contains the specific resource ID and expiration date. The server decrypts it, validates the expiration, and grants access *solely* to that resource without requiring a session.
3. **Stateful Actions**:
   - When transferring complex state details between systems or pages that the user must not be able to read or modify.

---

## 4. Key Security Best Practices

1. **Avoid Object Deserialization (Use JSON)**:
   - Always encrypt plain strings or JSON strings (`Crypt::encrypt($json, false)`) rather than PHP objects or serialized arrays. Using `serialize: false` prevents PHP object injection/unserialization vulnerabilities.
2. **Always Enforce Expiration**:
   - Encrypted payloads do not expire automatically unless you embed an `expires_at` timestamp inside the payload and validate it on decryption. Never accept encrypted payloads indefinitely.
3. **Ensure Cryptographic Integrity**:
   - Do not trust the payload's content until decryption succeeds. Laravel's encrypter automatically verifies the ciphertext MAC during decryption.
