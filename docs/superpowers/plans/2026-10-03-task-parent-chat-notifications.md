# Task, Parent, and Chat Notifications Implementation Plan

> **For agentic workers:** Implement task-by-task with test-first changes and independent verification.

**Goal:** Add verified native FCM notifications for task creation, submission, verification, calendar activity, and chat, including eligible parent accounts, with allowlisted mobile routes and idempotent delivery.

**Architecture:** Reuse `MobileFcmNotificationService` and its device delivery ledger. Add event-boundary calls only after successful writes/transactions, with recipient filtering derived from existing student-parent relationships. Keep web/PWA notifications separate from native FCM. The Flutter app parses typed payloads and maps only allowlisted semantic types to `/tugas`, `/kalender`, and `/chat`.

**Tech Stack:** Laravel/PHP 8.2, PHPUnit, Eloquent observers/services, Flutter/Dart, Firebase Cloud Messaging.

**Spec:** User request in chat: notify students/parents for new tasks, calendars, chat, submitted tasks awaiting verification, and verified tasks.

## Global Constraints

- Preserve unrelated dirty changes in the original backend/mobile checkouts.
- Never commit or print Firebase credentials, API keys, bearer tokens, or device tokens.
- Do not claim Android delivery without provider `sent=1` and device evidence.
- Use after-commit behavior for business-event notifications.
- Use stable notification types and allowlisted routes; never navigate from arbitrary payload URLs.
- Parent recipients require an existing authorized student-parent relationship and an active, non-revoked FCM token.
- Do not deploy until tests, CI, human approval, merge, configuration, and controlled smoke test are complete.

## Notification Matrix

| Event | Recipient | Type | Route | Evidence |
|---|---|---|---|---|
| Task created for student | targeted student + eligible linked parent | `task` | `/tugas` | feature test + provider + Android |
| Student submitted task | eligible verifier + linked parent | `task` | `/tugas` | feature test + provider + Android |
| Task verified | student + linked parent | `task` | `/tugas` | feature test + provider + Android |
| Calendar published/reminder | configured audience and eligible linked parents where applicable | `calendar` | `/kalender` | existing tests + recipient test |
| New chat message | intended recipient(s), optional linked parent only if policy permits | `chat` | `/chat` | feature test + provider + Android |

## Task 1: Audit exact event boundaries and relationships

- Read task creation, checklist submission, verification, chat send, calendar creation, parent/student models, and current Flutter notification service/router.
- Record exact classes and existing authorization rules in the implementation PR description.
- Reject any event whose recipient relationship or mobile route cannot be proven.

## Task 2: Backend RED/GREEN for task creation

- Add a focused feature test that creates a task through the real web write boundary and asserts one typed FCM delivery to the targeted active student and each eligible linked parent, excluding unrelated accounts and revoked tokens.
- Run the test and verify the expected failure before implementing.
- Add the minimal after-commit event/observer/service call and stable delivery keys.
- Run the focused test to green; add rollback/idempotency coverage.

## Task 3: Backend RED/GREEN for submitted and verified task states

- Add tests for student submission notifying eligible verifier and parent, and verification notifying student and parent.
- Verify duplicate updates do not resend the same semantic event.
- Implement only at the actual submit/verify boundaries, preserving existing PWA behavior.
- Run focused task tests and then the related notification suite.

## Task 4: Backend RED/GREEN for chat and calendar recipient expansion

- Add tests for chat recipient delivery and calendar parent scope, including sender exclusion and inactive/revoked-token exclusion.
- Implement the minimum recipient resolver and calls after successful message/calendar writes.
- Keep chat authorization unchanged; never notify unauthorized related accounts.

## Task 5: Mobile contract and navigation

- Add Dart tests first for typed payload parsing, malformed payload fallback, and allowlisted mapping: `task -> /tugas`, `calendar -> /kalender`, `chat -> /chat`.
- Run tests RED, implement in the existing FCM notification abstraction, then run analyzer and tests.
- Verify foreground/background/cold-start handling and idempotent tap consumption from existing app architecture.

## Task 6: Full verification

- Backend: focused PHPUnit, full PHPUnit, PHP lint, Composer validate, and `git diff --check`.
- Mobile: `flutter test`, `flutter analyze`, debug APK build, and inspect APK/package metadata.
- Review diff for secrets and unrelated files.
- Record provider send, displayed notification, and tap-navigation evidence separately.

## Task 7: Release gates

- Commit backend and mobile changes separately on isolated branches.
- Push and open PRs; wait for CI and automated reviews.
- Human approval is required before merge; do not merge or deploy on automated review alone.
- After merge, verify production workflow, migrations/configuration, rollback path, and controlled smoke test with test accounts only.
- Verify production endpoint and Android evidence before claiming completion.
