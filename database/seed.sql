-- Agrora fictional development data. Do not import into the live database.
-- First import schema.sql into an empty local/development database.
-- Then select that database and import this file using phpMyAdmin.
-- Fixed demo public IDs make repeat imports safe: existing rows stay unchanged.
-- Prices and harvest dates are illustrative, not market information.
-- No image records are inserted, so the website uses its placeholder image.
-- Ten listings exercise the nine-item page size and second page.

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO listings
  (public_id, crop_type, variety, region, quantity_tons, price_per_kg,
   price_note, harvest_start, harvest_end, seller_name, seller_phone,
   seller_email, description, created_at)
VALUES
  ('demo-001', 'ntomates', 'marmande', 'thessalonikis', 2.50, 1.20,
   NULL, '2026-09', '2026-10', 'Δοκιμαστικός Παραγωγός 01', '0000000000',
   'demo01@example.com', 'Δοκιμαστική αγγελία ντομάτας για έλεγχο της εφαρμογής. Δεν διατίθεται προς πώληση.', '2026-09-30 10:00:00'),
  ('demo-002', 'patates', 'spunta', 'dramas', 15.00, 0.45,
   NULL, '2026-09', '2026-11', 'Δοκιμαστικός Παραγωγός 02', '0000000000',
   'demo02@example.com', 'Δοκιμαστική αγγελία πατάτας μεγάλης ποσότητας. Δεν διατίθεται προς πώληση.', '2026-09-29 10:00:00'),
  ('demo-003', 'lachana', 'kilaton', 'serron', 8.00, NULL,
   'Τιμή κατόπιν συνεννόησης (δείγμα)', '2026-10', '2027-01', 'Δοκιμαστικός Παραγωγός 03', '0000000000',
   NULL, 'Δοκιμαστική αγγελία χωρίς αριθμητική τιμή ή email. Δεν διατίθεται προς πώληση.', '2026-09-28 10:00:00'),
  ('demo-004', 'karota', NULL, 'thessalonikis', 0.75, 0.65,
   NULL, '2026-10', '2026-12', 'Δοκιμαστικός Παραγωγός 04', '0000000000',
   'demo04@example.com', NULL, '2026-09-27 10:00:00'),
  ('demo-005', 'ntomates', 'cherry', 'imathias', 1.25, 2.10,
   NULL, '2026-09', '2026-09', 'Δοκιμαστικός Παραγωγός 05', '0000000000',
   'demo05@example.com', 'Δοκιμαστική αγγελία με συγκομιδή ενός μήνα. Δεν διατίθεται προς πώληση.', '2026-09-26 10:00:00'),
  ('demo-006', 'patates', 'agria', 'pellas', 25.00, NULL,
   NULL, '2026-10', '2026-11', 'Δοκιμαστικός Παραγωγός 06', '0000000000',
   NULL, 'Δοκιμαστική αγγελία χωρίς τιμή και σημείωση τιμής. Δεν διατίθεται προς πώληση.', '2026-09-25 10:00:00'),
  ('demo-007', 'lachana', NULL, 'dramas', 3.50, 0.30,
   NULL, '2026-11', '2027-02', 'Δοκιμαστικός Παραγωγός 07', '0000000000',
   'demo07@example.com', 'Δοκιμαστική αγγελία χωρίς ποικιλία. Δεν διατίθεται προς πώληση.', '2026-09-24 10:00:00'),
  ('demo-008', 'aggoyria', NULL, 'serron', 0.50, 0.85,
   NULL, '2026-09', '2026-10', 'Δοκιμαστικός Παραγωγός 08', '0000000000',
   'demo08@example.com', 'Δοκιμαστική αγγελία μικρής ποσότητας. Δεν διατίθεται προς πώληση.', '2026-09-23 10:00:00'),
  ('demo-009', 'kremmydia', NULL, 'imathias', 12.00, 0.40,
   NULL, '2026-08', '2026-09', 'Δοκιμαστικός Παραγωγός 09', '0000000000',
   NULL, 'Δοκιμαστική αγγελία κρεμμυδιών. Δεν διατίθεται προς πώληση.', '2026-09-22 10:00:00'),
  ('demo-010', 'ntomates', 'belladonna', 'pellas', 4.00, 1.05,
   NULL, '2026-10', '2026-12', 'Δοκιμαστικός Παραγωγός 10', '0000000000',
   'demo10@example.com', 'Δοκιμαστική αγγελία για τη δεύτερη σελίδα αποτελεσμάτων. Δεν διατίθεται προς πώληση.', '2026-09-21 10:00:00')
ON DUPLICATE KEY UPDATE public_id = VALUES(public_id);

COMMIT;
