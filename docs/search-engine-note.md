# Search engine note (V2)

LocaGed currently uses **Laravel Scout + Typesense** for document search indexing.

- Active driver: `typesense`
- Main config: [`config/scout.php`](../config/scout.php) and `.env` (`SCOUT_DRIVER`, `TYPESENSE_*`)
- OCR output is indexed in this Typesense pipeline after document approval workflows.
