# Changelog

## 10.0 — 2026-11-08

- Cope with RTL
- Right align line numbers
- Review CSS (font size and cells padding)
- Extend MetaRecord whatever is the result of getting existing revisions
- Use typed getter/setter Cursor method, DC 2.40+
- Enforces some verification before registering module

## 9.0 — 2026-08-03

- Avoid using magic _get(), _set() methods of MetaRecord (2.39+)
- Avoid using magic methods for user preferences and blog settings (2.39+)
- Forgot these ones
- Use MetaRecord::strField(), MetaRecord::intField() and MetaRecord::boolField() whenever is possible

## 8.1 — 2026-05-06

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 8.0 — 2026-04-29

- Use new MetaRecord::cardinal() method

## 7.2 — 2026-04-10

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 7.1 — 2025-09-22

- Use backend services rather than static calls

## 7.0 — 2025-09-07

- DC 2.36 compliance

## 6.2 — 2025-06-04

- Switch from jQuery to Vanilla JS

## 6.1 — 2025-05-05

- Use Strong(…) rather than Text('strong', …)

## 6.0 — 2025-05-03

- Uninstall table name must no include db prefix DC 2.34+

## 5.11 — 2025-02-26

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 5.10 — 2025-02-25

- Cope with small devices for list of revisions
- Forgot to remove this

## 5.9 — 2025-02-24

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 5.8 — 2025-02-04

- Use standard monospace font (from DC)

## 5.7 — 2024-11-12

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 5.6 — 2024-09-07

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 5.5 — 2024-06-24

- Add error catching on adding revision

## 5.4.1 — 2024-06-09

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 5.4 — 2024-05-18

- Switch from PNG to SVG for unlocked revision
- Switch from PNG to SVG for locked revision, 2.30+

## 5.3.2 — 2024-01-29

- Cope with string post ID for some behaviors' callback

## 5.3.1 — 2024-01-08

- Fix revision system on page modification (post ID is now treated everywhere as integer), fixes #6 List of revisions on page edition can now be open/close

## 5.3 — 2023-12-06

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 5.2 — 2023-11-11

- Use SqlStatement

## 5.1 — 2023-10-27

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 5.0 — 2023-10-24

- Fix URL composition
- Use instanced item constants rather than interface ones
- Avoid using magic methods as far as possible
- No need to keep old admin URL schema tests
- 2.28 compliance
- Type hint (2.27 and 2.28 compliance)

## 4.0 — 2023-08-23

- Cope with revision timezone, should fix #4
- post commit
- Dotclear 2.27 compliance, PHP 8.1+

## 3.3 — 2023-07-03

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 3.2 — 2023-06-29

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 3.1.2 — 2023-06-27

- Add local static $init variable

## 3.1.1 — 2023-06-23

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.

## 3.1 — 2023-06-20

- Use admin URLs
- Fix description

## 3.0 — 2023-06-10

- Switch to namespace
- Prepare 2.26
- Switch to best effort even with exception thrown

## 2.0 — 2023-03-07

- Prepare 2.25
- Minified versions
- Move css in folder

## 1.0 — 2022-11-28

- Avoid notice on new installation

## 0.8 — 2022-11-22

- 2.24 compliance
- Avoid confusion with global $redir_url

## 0.7 — 2022-08-15

- Update default branch in _define.php for alternative repository
- Add alternative repository

## 0.6 — 2021-08-06

- Dotclear 2.19 compliance
- 2.19 - PHP 7+

## 0.5.1 — 2021-02-01

- Fix js exception for new post

## 0.5 — 2020-04-08

- Dotclear 2.16 compliance, no more inline JS

## 0.4 — 2019-02-04

- Add revisions purge on set of entries (posts/pages)
- Add "purge revisions" button in post/page edit mode
- Use let and const rather than var (ES2015/ES6)
- short notation for array (array() → [])
- Using dcPage::cssLoad, dcPage::jsLoad and dcPage::getPF is a better way to load plugin resources

## 0.3 — 2013-10-04

- No more direct style injections
- Various
- &lt;/p&gt; is better than &lt;p&gt; in order to close a &lt;p&gt; :-)
- new _define.php
- No more $GLOBALS at top level, and various
- 2.6 compliance
- various (next release will be 0.3)
- 2.6 blog pref compliance
- Autres correctifs et améliorations.

## 0.2.1 — 2013-07-12

- 0.2.1
- Rewording
- Minor adjustments on diff display

## 0.2 — 2012-12-15

- Minor adjustements
- Comprehensive revision numbering (on display)
- Cope with pages, view diff in native post format, some bug fixes, some minor adjustments
- Untrack _disabled

## 0.1 — 2012-12-14

- Version de maintenance, sans changement fonctionnel notable relevé dans l’historique.
