-- =====================================================================
-- AI-Based Pharmacy Management System
-- Seed data (Phase 4): sample medicines and stock batches
--
-- This file is for DEVELOPMENT and for the project demonstration. It gives
-- the catalogue something to show before the admin panel exists (Phase 7).
--
-- Running it again first deletes the sample rows, so it can be re-run safely.
-- It does NOT touch the users table, so your accounts stay as they are.
-- =====================================================================

USE pharmacy_db;

-- Remove old sample data. Child rows first, because of the foreign keys.
DELETE FROM order_item_batches;
DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM cart;
DELETE FROM stock_alerts;
DELETE FROM inventory_batches;
DELETE FROM medicines;

-- Start the ID numbering again from 1.
ALTER TABLE medicines AUTO_INCREMENT = 1;
ALTER TABLE inventory_batches AUTO_INCREMENT = 1;


-- ---------------------------------------------------------------------
-- medicines (FR-34)
-- stock_qty is filled in at the bottom of this file from the batches,
-- exactly the way PHP will recalculate it from Phase 5 onwards.
-- ---------------------------------------------------------------------
INSERT INTO medicines
    (name, sku, category, drap_category, manufacturer, description, price, reorder_threshold)
VALUES
    ('Paracetamol 500 mg Tablets', 'PCM-500', 'Analgesic', 'General Sale', 'Karachi Remedies',
     'Relieves mild to moderate pain and reduces fever. Adults: one tablet every 4 to 6 hours, maximum 8 tablets in 24 hours.',
     45.00, 40),
    ('Ibuprofen 400 mg Tablets', 'IBU-400', 'Analgesic', 'General Sale', 'Indus Pharma Labs',
     'An anti-inflammatory painkiller for headache, toothache and period pain. Take with food to protect the stomach.',
     95.00, 30),
    ('Diclofenac 50 mg Tablets', 'DIC-050', 'Analgesic', 'Prescription Only', 'Ravi Healthcare',
     'Reduces pain, swelling and stiffness in joints and muscles. Use only as directed by a doctor.',
     120.00, 20),
    ('Amoxicillin 250 mg Capsules', 'AMX-250', 'Antibiotic', 'Prescription Only', 'Indus Pharma Labs',
     'A penicillin antibiotic for chest, ear and throat infections. Always finish the full course prescribed by your doctor.',
     185.00, 20),
    ('Azithromycin 500 mg Tablets', 'AZI-500', 'Antibiotic', 'Prescription Only', 'Karachi Remedies',
     'A short-course antibiotic, usually taken once daily for three to five days. Take one hour before or two hours after a meal.',
     420.00, 15),
    ('Ciprofloxacin 500 mg Tablets', 'CIP-500', 'Antibiotic', 'Prescription Only', 'Ravi Healthcare',
     'Used for urinary and stomach infections. Drink plenty of water and avoid dairy products at the time of the dose.',
     260.00, 15),
    ('Cetirizine 10 mg Tablets', 'CTZ-010', 'Antihistamine', 'General Sale', 'Indus Pharma Labs',
     'Relieves sneezing, runny nose, itchy eyes and skin allergy. One tablet at night, as it can cause mild drowsiness.',
     95.00, 25),
    ('Loratadine 10 mg Tablets', 'LOR-010', 'Antihistamine', 'General Sale', 'Karachi Remedies',
     'A non-drowsy allergy tablet for hay fever and skin rash. One tablet daily.',
     110.00, 20),
    ('Cetirizine Syrup 60 ml', 'CTZ-S60', 'Antihistamine', 'General Sale', 'Indus Pharma Labs',
     'A children''s allergy syrup with a measuring spoon. Follow the dose on the label for your child''s age and weight.',
     140.00, 15),
    ('Omeprazole 20 mg Capsules', 'OMZ-020', 'Antacid', 'Pharmacy Only', 'Ravi Healthcare',
     'Reduces stomach acid for heartburn and reflux. Take one capsule in the morning before food.',
     320.00, 15),
    ('Antacid Suspension 120 ml', 'ANT-120', 'Antacid', 'General Sale', 'Karachi Remedies',
     'A mint-flavoured liquid that settles acidity and indigestion. Shake the bottle well before each dose.',
     165.00, 15),
    ('Metformin 500 mg Tablets', 'MTF-500', 'Antidiabetic', 'Prescription Only', 'Ravi Healthcare',
     'Helps control blood sugar in type 2 diabetes. Take with meals to reduce stomach upset.',
     210.00, 20),
    ('Glimepiride 2 mg Tablets', 'GLM-002', 'Antidiabetic', 'Prescription Only', 'Indus Pharma Labs',
     'Lowers blood sugar by helping the body release more insulin. Do not skip meals while taking it.',
     280.00, 15),
    ('Amlodipine 5 mg Tablets', 'AML-005', 'Cardiovascular', 'Prescription Only', 'Karachi Remedies',
     'Lowers high blood pressure by relaxing the blood vessels. One tablet daily at the same time each day.',
     190.00, 20),
    ('Atorvastatin 10 mg Tablets', 'ATV-010', 'Cardiovascular', 'Prescription Only', 'Ravi Healthcare',
     'Lowers cholesterol to protect the heart. Usually taken in the evening.',
     340.00, 15),
    ('Aspirin 75 mg Tablets', 'ASP-075', 'Cardiovascular', 'Pharmacy Only', 'Indus Pharma Labs',
     'A low dose taken daily to help prevent blood clots. Only use long term on a doctor''s advice.',
     85.00, 25),
    ('Salbutamol Inhaler 100 mcg', 'SLB-100', 'Respiratory', 'Prescription Only', 'Indus Pharma Labs',
     'A reliever inhaler that opens the airways quickly during asthma symptoms. Shake before use.',
     650.00, 10),
    ('Cough Syrup 120 ml', 'CGH-120', 'Respiratory', 'General Sale', 'Karachi Remedies',
     'Soothes a dry, tickly cough. Not suitable for children under six years.',
     180.00, 20),
    ('Montelukast 10 mg Tablets', 'MNT-010', 'Respiratory', 'Prescription Only', 'Ravi Healthcare',
     'Helps prevent asthma symptoms and seasonal allergy. Take one tablet in the evening.',
     390.00, 12),
    ('ORS Sachets (pack of 6)', 'ORS-006', 'Rehydration', 'General Sale', 'Karachi Remedies',
     'Replaces salts and water lost during diarrhoea or heat. Dissolve one sachet in 200 ml of clean water.',
     90.00, 40),
    ('Vitamin D3 Drops 15 ml', 'VTD-015', 'Vitamins', 'General Sale', 'Indus Pharma Labs',
     'A daily vitamin D supplement for infants and adults. Use the dropper provided.',
     450.00, 12),
    ('Multivitamin Tablets (30)', 'MVT-030', 'Vitamins', 'General Sale', 'Ravi Healthcare',
     'A daily multivitamin with minerals to support general health. One tablet after breakfast.',
     520.00, 15),
    ('Calcium + Vitamin D Tablets', 'CAL-500', 'Vitamins', 'General Sale', 'Karachi Remedies',
     'Supports bone strength, often used with a doctor''s advice during pregnancy or after menopause.',
     380.00, 15),
    ('Iron + Folic Acid Tablets', 'IRN-FOL', 'Vitamins', 'General Sale', 'Indus Pharma Labs',
     'Helps treat and prevent iron deficiency anaemia. Take with orange juice to help absorption.',
     240.00, 20);


-- ---------------------------------------------------------------------
-- inventory_batches (FR-35)
-- Each row is one delivery. fifo_order 1 is the oldest batch of a medicine,
-- so FIFO always sells from the lowest fifo_order first (SRS 2.6).
-- quantity is how many units of that batch are LEFT.
-- ---------------------------------------------------------------------
INSERT INTO inventory_batches
    (medicine_id, quantity, supplier_name, cost_price, received_date, expiry_date, fifo_order, is_depleted)
VALUES
    ( 1,  60, 'Multan Medical Supplies',   27.00, '2026-02-15', '2027-02-10', 1, 0),
    ( 1,  90, 'Punjab Pharma Distributors',   28.80, '2026-08-24', '2027-11-07', 2, 0),
    ( 2,  60, 'Punjab Pharma Distributors',   57.00, '2026-04-16', '2027-02-10', 1, 0),
    ( 2,  60, 'National Drug House',   60.80, '2026-08-24', '2028-06-14', 2, 0),
    ( 3,  30, 'National Drug House',   72.00, '2025-11-17', '2026-10-01', 1, 0),
    ( 3,  90, 'Multan Medical Supplies',   76.80, '2026-07-15', '2028-02-15', 2, 0),
    ( 4,  80, 'Multan Medical Supplies',  111.00, '2026-04-16', '2027-02-10', 1, 0),
    ( 4,  60, 'Punjab Pharma Distributors',  118.40, '2026-07-05', '2027-11-07', 2, 0),
    ( 5, 100, 'Punjab Pharma Distributors',  252.00, '2026-04-16', '2027-02-10', 1, 0),
    ( 5,  60, 'National Drug House',  268.80, '2026-07-05', '2027-11-07', 2, 0),
    ( 6,  50, 'National Drug House',  156.00, '2026-04-16', '2027-05-31', 1, 0),
    ( 6,  60, 'Multan Medical Supplies',  166.40, '2026-07-05', '2028-01-26', 2, 0),
    ( 7, 100, 'Multan Medical Supplies',   57.00, '2026-03-17', '2027-04-01', 1, 0),
    ( 7,  60, 'Punjab Pharma Distributors',   60.80, '2026-08-24', '2028-01-26', 2, 0),
    ( 8,  60, 'Punjab Pharma Distributors',   66.00, '2026-05-16', '2027-02-10', 1, 0),
    ( 8,  60, 'National Drug House',   70.40, '2026-08-09', '2028-06-14', 2, 0),
    ( 9,   0, 'National Drug House',   86.80, '2026-02-25', '2027-07-10', 1, 1),
    (10,   5, 'Multan Medical Supplies',  198.40, '2026-04-16', '2027-05-11', 1, 0),
    (11, 100, 'Punjab Pharma Distributors',   99.00, '2026-03-17', '2027-07-30', 1, 0),
    (11, 150, 'National Drug House',  105.60, '2026-07-05', '2028-03-26', 2, 0),
    (12,  60, 'National Drug House',  126.00, '2026-04-16', '2027-04-01', 1, 0),
    (12,  90, 'Multan Medical Supplies',  134.40, '2026-08-24', '2028-03-26', 2, 0),
    (13, 100, 'Multan Medical Supplies',  168.00, '2026-03-17', '2027-07-30', 1, 0),
    (13, 150, 'Punjab Pharma Distributors',  179.20, '2026-07-25', '2027-11-07', 2, 0),
    (14,  40, 'Punjab Pharma Distributors',  114.00, '2026-04-16', '2027-05-31', 1, 0),
    (14, 150, 'National Drug House',  121.60, '2026-08-09', '2028-06-14', 2, 0),
    (15,  80, 'National Drug House',  204.00, '2026-05-16', '2027-05-31', 1, 0),
    (15,  60, 'Multan Medical Supplies',  217.60, '2026-07-25', '2028-03-26', 2, 0),
    (16, 100, 'Multan Medical Supplies',   51.00, '2026-02-15', '2027-02-10', 1, 0),
    (16, 150, 'Punjab Pharma Distributors',   54.40, '2026-08-24', '2028-03-26', 2, 0),
    (17,   0, 'Punjab Pharma Distributors',  403.00, '2026-02-25', '2027-07-10', 1, 1),
    (18,  80, 'National Drug House',  108.00, '2026-05-16', '2027-05-31', 1, 0),
    (18,  60, 'Multan Medical Supplies',  115.20, '2026-07-05', '2028-03-26', 2, 0),
    (19,   4, 'Multan Medical Supplies',  241.80, '2026-04-16', '2027-05-11', 1, 0),
    (20,  80, 'Punjab Pharma Distributors',   54.00, '2026-05-16', '2027-07-30', 1, 0),
    (20, 120, 'National Drug House',   57.60, '2026-07-25', '2028-01-26', 2, 0),
    (21, 100, 'National Drug House',  270.00, '2026-02-15', '2027-02-10', 1, 0),
    (21,  60, 'Multan Medical Supplies',  288.00, '2026-08-09', '2028-03-26', 2, 0),
    (22,  30, 'Multan Medical Supplies',  312.00, '2025-11-17', '2026-10-01', 1, 0),
    (22,  90, 'Punjab Pharma Distributors',  332.80, '2026-07-15', '2028-02-15', 2, 0),
    (23,  50, 'Punjab Pharma Distributors',  228.00, '2026-02-15', '2027-07-30', 1, 0),
    (23,  90, 'National Drug House',  243.20, '2026-07-05', '2027-11-07', 2, 0),
    (24,  50, 'National Drug House',  144.00, '2026-02-15', '2027-05-31', 1, 0),
    (24, 150, 'Multan Medical Supplies',  153.60, '2026-08-09', '2028-06-14', 2, 0);



-- ---------------------------------------------------------------------
-- Fill in the summary columns on medicines from the batches.
-- Only batches that still have stock AND have not expired are counted.
-- This is the same rule the PHP code will use from Phase 5 onwards.
-- ---------------------------------------------------------------------
UPDATE medicines m
SET
    m.stock_qty = (
        SELECT COALESCE(SUM(b.quantity), 0)
        FROM inventory_batches b
        WHERE b.medicine_id = m.medicine_id
          AND b.quantity > 0
          AND b.expiry_date > CURDATE()
    ),
    m.expiry_date = (
        SELECT MIN(b.expiry_date)
        FROM inventory_batches b
        WHERE b.medicine_id = m.medicine_id
          AND b.quantity > 0
          AND b.expiry_date > CURDATE()
    );