/**
 * The error body of the contract, as an exception the screens can read.
 *
 * Every endpoint answers failures in one shape, so the screens parse one thing:
 * a code they can branch on, a message already written for the person reading
 * it, and the fields that were rejected.
 */
export class ApiError extends Error {
  constructor(status, code, message, details) {
    super(message);

    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.details = details ?? [];
  }

  /** The token is gone or expired, so the screen has to send the user back to sign in. */
  get isUnauthenticated() {
    return this.status === 401;
  }

  /** A field-level message for a form, keyed by field name. */
  issuesByField() {
    const issues = {};

    for (const detail of this.details) {
      issues[detail.field] = detail.issue;
    }

    return issues;
  }
}
