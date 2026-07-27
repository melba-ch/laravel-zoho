# Changelog
All notable changes to this project will be documented in this file.

## [Unreleased]

### Changed
- **(Behavioral)** `IdentityProviderException` thrown by `ZohoAuthProvider::checkResponse()`
  now carries the **full parsed response body** via `getResponseBody()`, instead of a single
  extracted string (`$data['error']` / `$data['message']`). This matches the League OAuth2
  convention (the 3rd constructor argument is the response body) and is required to diagnose
  unknown errors whose body carries no `error`/`code` key. Consumers that read
  `getResponseBody()` and expected a string must adapt.

### Fixed
- `ZohoAuthProvider::checkResponse()` no longer assumes a `code`/`message` key is present
  on error responses. Zoho may return an HTTP >= 400 whose body only carries an `error`
  key (or none at all), which previously raised an `Undefined array key "code"`
  `ErrorException` instead of a proper `IdentityProviderException` — masking the real
  error and escaping typed `catch` blocks. All error responses now throw
  `IdentityProviderException` carrying the parsed body.
- A valid HTTP 200 response is no longer misclassified as an error when its body contains a
  `code` key (eg. `{"code": "SUCCESS"}`): only an HTTP >= 400 status or an `error` key marks
  a response as failed.
