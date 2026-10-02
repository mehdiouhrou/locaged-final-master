-- ============================================================
-- SPCR — Reset propre + création catégories/sous-catégories
-- ============================================================
-- ATTENTION : supprime TOUT (subcategories, categories, et
-- met category_id/subcategory_id à NULL sur les documents)
-- À exécuter AVANT la création des vrais comptes utilisateurs
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- 1. Détacher les documents des catégories (évite FK error)
UPDATE documents SET category_id = NULL, subcategory_id = NULL;

-- 2. Vider les accès catégories/sous-catégories utilisateurs
DELETE FROM user_category_access;
DELETE FROM user_subcategory_access;

-- 3. Vider sous-catégories puis catégories
DELETE FROM subcategories;
DELETE FROM categories;

-- Reset auto-increment pour IDs propres
ALTER TABLE subcategories AUTO_INCREMENT = 1;
ALTER TABLE categories    AUTO_INCREMENT = 1;

SET foreign_key_checks = 1;

-- ============================================================
-- CATÉGORIES (4)
-- ============================================================
INSERT INTO categories (name, description, created_at, updated_at) VALUES
('REDAL',     'Documents contractuels REDAL — Gestion Déléguée Eau & Électricité Rabat',  NOW(), NOW()),
('SRM',       'Documents contractuels SRM — Société de Régie Multiservices',              NOW(), NOW()),
('SPC',       'Documents SPCR — Autorité Délégante, audits, marchés internes',            NOW(), NOW()),
('Transport', 'Gestion Déléguée Transport Urbain — opérateurs Rabat-Salé-Kénitra',        NOW(), NOW());

-- ============================================================
-- SOUS-CATÉGORIES REDAL (27)
-- ============================================================
INSERT INTO subcategories (category_id, name, created_at, updated_at)
SELECT id, sub.name, NOW(), NOW()
FROM categories,
(SELECT 'Dossier N°1 : Communication des états d''arrêtés des comptes de l''AD' AS name
UNION ALL SELECT 'Dossier N°2 : Communication du Reporting des Comptes de l''Autorité Délégante'
UNION ALL SELECT 'Dossier N°3 : Copie de la liasse fiscale des états comptables annuels de l''année N'
UNION ALL SELECT 'Dossier N°4 : Indicateur de performance Commerciale (Pénalités)'
UNION ALL SELECT 'Dossier N°5 : Situation des consommations illicites par compteur'
UNION ALL SELECT 'Dossier N°6 : Bordereau des prix actualisé'
UNION ALL SELECT 'Dossier N°7 : Etat de valorisation avec les écarts sur les budgets approuvés'
UNION ALL SELECT 'Dossier N°8 : Justificatifs des investissements en cours de réalisation'
UNION ALL SELECT 'Dossier N°9 : Reporting achat'
UNION ALL SELECT 'Dossier N°10 : Suivi des dépenses relatives aux projets d''investissements'
UNION ALL SELECT 'Dossier N°11 : Rapport des Commissaires aux comptes (Général & Spécial)'
UNION ALL SELECT 'Dossier N°12 : Etat des puissances appelées par poste source et par transformateur HTB/HTA'
UNION ALL SELECT 'Dossier N°13 : Etat des rendements cumulés et glissants du mois n-1'
UNION ALL SELECT 'Dossier N°14 : Rapport sur les interruptions partielles ou générales'
UNION ALL SELECT 'Dossier N°15 : Rapport sur l''état des incidents'
UNION ALL SELECT 'Dossier N°16 : Compte de rendu annuel'
UNION ALL SELECT 'Dossier N°17 : Budget d''investissement de l''année N+1'
UNION ALL SELECT 'Dossier N°18 : Budget Fonctionnement de l''année N+1'
UNION ALL SELECT 'Dossier N°19 : Plan pluriannuel Glissant sur 5 ans de l''année N+1'
UNION ALL SELECT 'Dossier N°20 : Inventaire annuel des biens des Services Délégués'
UNION ALL SELECT 'Dossier N°21 : Publication du programme prévisionnel des marchés relatifs au programme d''investissements'
UNION ALL SELECT 'Dossier N°22 : Liste assurances'
UNION ALL SELECT 'Dossier N°23 : Cautions'
UNION ALL SELECT 'Dossier N°24 : Schémas directeurs (eau, électricité, assainissement)'
UNION ALL SELECT 'Dossier N°25 : Manuels des procédures'
UNION ALL SELECT 'Dossier N°26 : Conventions cadres signées'
UNION ALL SELECT 'Dossier N°27 : Dossier CA'
) AS sub
WHERE categories.name = 'REDAL';

-- ============================================================
-- SOUS-CATÉGORIES SRM (33)
-- ============================================================
INSERT INTO subcategories (category_id, name, created_at, updated_at)
SELECT id, sub.name, NOW(), NOW()
FROM categories,
(SELECT 'SR-Dossier N°1 : Plan pluriannuel d''investissement (N+1 à N+5)' AS name
UNION ALL SELECT 'SR-Dossier N°2 : Budget annuel d''investissement (N+1)'
UNION ALL SELECT 'SR-Dossier N°3 : KPI performances techniques (Électricité)'
UNION ALL SELECT 'SR-Dossier N°4 : KPI performances techniques (Eau)'
UNION ALL SELECT 'SR-Dossier N°5 : KPI performances techniques (Assainissement)'
UNION ALL SELECT 'SR-Dossier N°6 : KPI performances techniques (STEP, Séchage & réutilisation)'
UNION ALL SELECT 'SR-Dossier N°7 : KPI performances clientèle'
UNION ALL SELECT 'SR-Dossier N°8 : Suivi du patrimoine initial'
UNION ALL SELECT 'SR-Dossier N°9 : Inventaire permanent des stocks / Inventaire physique des stocks et immobilisations'
UNION ALL SELECT 'SR-Dossier N°10 : Indicateurs achats/ventes eau & électricité'
UNION ALL SELECT 'SR-Dossier N°11 : Canevas des indicateurs de performance investissement (Etat des réalisations mensuelles)'
UNION ALL SELECT 'SR-Dossier N°12 : Facturation des travaux réalisés par la société (Bordereau des prix : annexe 15 REDAL)'
UNION ALL SELECT 'SR-Dossier N°13 : Schémas directeurs (eau, électricité, assainissement)'
UNION ALL SELECT 'SR-Dossier N°14 : Projections (N à N+5)'
UNION ALL SELECT 'SR-Dossier N°15 : Budget Fonctionnement de l''année N+1'
UNION ALL SELECT 'SR-Dossier N°16 : Budget de trésorerie consolidé (N+1)'
UNION ALL SELECT 'SR-Dossier N°17 : Compte rendu financier'
UNION ALL SELECT 'SR-Dossier N°18 : Situations mensuelles d''exécution budgétaire (Réalisations)'
UNION ALL SELECT 'SR-Dossier N°19 : Comptes de produits et charges (CPC) – consolidé et par métier (Etat Comptabilité analytique)'
UNION ALL SELECT 'SR-Dossier N°20 : Rapport de performances financières'
UNION ALL SELECT 'SR-Dossier N°21 : État des soldes de gestion (ESG) – consolidé et par métier'
UNION ALL SELECT 'SR-Dossier N°22 : Manuel des procédures comptables'
UNION ALL SELECT 'SR-Dossier N°23 : Situation mensuelle du compte FEC / Arrêté annuel du compte FEC'
UNION ALL SELECT 'SR-Dossier N°24 : Copie de la liasse fiscale'
UNION ALL SELECT 'SR-Dossier N°25 : Etats Comptabilité budgétaire'
UNION ALL SELECT 'SR-Dossier N°26 : Rapport Commissaire aux Comptes'
UNION ALL SELECT 'SR-Dossier N°27 : Liste assurances'
UNION ALL SELECT 'SR-Dossier N°28 : Versement des échéances dettes ONEE'
UNION ALL SELECT 'SR-Dossier N°29 : Convention cadre signée entre les pouvoirs publics, l''ONEE et les partenaires sociaux'
UNION ALL SELECT 'SR-Dossier N°30 : Liste du personnel (actualisée)'
UNION ALL SELECT 'SR-Dossier N°31 : Plan de formation annuel et pluriannuel'
UNION ALL SELECT 'SR-Dossier N°32 : Engagements sociaux et rapports aux partenaires sociaux'
UNION ALL SELECT 'SR-Dossier N°33 : Canevas des indicateurs de performance RH'
) AS sub
WHERE categories.name = 'SRM';

-- ============================================================
-- SOUS-CATÉGORIES SPC (9)
-- ============================================================
INSERT INTO subcategories (category_id, name, created_at, updated_at)
SELECT id, sub.name, NOW(), NOW()
FROM categories,
(SELECT 'SP-Dossier N°1 : Budget annuel du SPC (FEC REDAL & FEC SRM)' AS name
UNION ALL SELECT 'SP-Dossier N°2 : Rapports d''activité du SPC'
UNION ALL SELECT 'SP-Dossier N°3 : Rapports d''audit'
UNION ALL SELECT 'SP-Dossier N°4 : Rapports Semestriels et annuels'
UNION ALL SELECT 'SP-Dossier N°5 : Marchés et BCs'
UNION ALL SELECT 'SP-Dossier N°6 : CRQ'
UNION ALL SELECT 'SP-Dossier N°7 : Rapports et Fiches Visites Terrains'
UNION ALL SELECT 'SP-Dossier N°8 : Etats du dossier RH (SDSPD)'
UNION ALL SELECT 'SP-Dossier N°9 : Conventions CAS'
) AS sub
WHERE categories.name = 'SPC';

-- ============================================================
-- SOUS-CATÉGORIES TRANSPORT (5)
-- ============================================================
INSERT INTO subcategories (category_id, name, created_at, updated_at)
SELECT id, sub.name, NOW(), NOW()
FROM categories,
(SELECT 'ALSA CITY BUS' AS name
UNION ALL SELECT 'Société Tramway Rabat Salé (STRS)'
UNION ALL SELECT 'STAREO SA'
UNION ALL SELECT 'SOCIETE MADERASATI'
UNION ALL SELECT 'Transport Kénitra'
) AS sub
WHERE categories.name = 'Transport';

-- ============================================================
-- VÉRIFICATION FINALE
-- ============================================================
SELECT c.name AS categorie, COUNT(s.id) AS nb_sous_cat
FROM categories c
LEFT JOIN subcategories s ON s.category_id = c.id
GROUP BY c.id, c.name
ORDER BY c.id;

SELECT COUNT(*) AS total_sous_categories FROM subcategories;
