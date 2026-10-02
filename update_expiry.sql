-- ============================================================
-- SPCR — Mise à jour durées d'expiration des sous-catégories
-- Permanent / Définitive → 99 ans
-- 5 à 10 ans → 10 ans
-- 10 ans après départ du salarié → 10 ans
-- Transport → 10 ans (confirmé client)
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- REDAL (27 sous-catégories)
-- ============================================================

-- 10 ans (la majorité)
UPDATE subcategories SET expiry_value = 10, expiry_unit = 'years'
WHERE name IN (
    'Dossier N°1 : Communication des états d''arrêtés des comptes de l''AD',
    'Dossier N°3 : Copie de la liasse fiscale des états comptables annuels de l''année N',
    'Dossier N°4 : Indicateur de performance Commerciale (Pénalités)',
    'Dossier N°5 : Situation des consommations illicites par compteur',
    'Dossier N°6 : Bordereau des prix actualisé',
    'Dossier N°7 : Etat de valorisation avec les écarts sur les budgets approuvés',
    'Dossier N°9 : Reporting achat',
    'Dossier N°10 : Suivi des dépenses relatives aux projets d''investissements',
    'Dossier N°11 : Rapport des Commissaires aux comptes (Général & Spécial)',
    'Dossier N°12 : Etat des puissances appelées par poste source et par transformateur HTB/HTA',
    'Dossier N°13 : Etat des rendements cumulés et glissants du mois n-1',
    'Dossier N°14 : Rapport sur les interruptions partielles ou générales',
    'Dossier N°15 : Rapport sur l''état des incidents',
    'Dossier N°17 : Budget d''investissement de l''année N+1',
    'Dossier N°18 : Budget Fonctionnement de l''année N+1',
    'Dossier N°20 : Inventaire annuel des biens des Services Délégués',
    'Dossier N°21 : Publication du programme prévisionnel des marchés relatifs au programme d''investissements',
    'Dossier N°22 : Liste assurances',
    'Dossier N°23 : Cautions'
);

-- Permanent (99 ans)
UPDATE subcategories SET expiry_value = 99, expiry_unit = 'years'
WHERE name IN (
    'Dossier N°2 : Communication du Reporting des Comptes de l''Autorité Délégante',
    'Dossier N°8 : Justificatifs des investissements en cours de réalisation',
    'Dossier N°16 : Compte de rendu annuel',
    'Dossier N°19 : Plan pluriannuel Glissant sur 5 ans de l''année N+1',
    'Dossier N°27 : Dossier CA'
);

-- Définitive (99 ans)
UPDATE subcategories SET expiry_value = 99, expiry_unit = 'years'
WHERE name IN (
    'Dossier N°24 : Schémas directeurs (eau, électricité, assainissement)',
    'Dossier N°25 : Manuels des procédures',
    'Dossier N°26 : Conventions cadres signées'
);

-- ============================================================
-- SPC (9 sous-catégories)
-- ============================================================

-- 10 ans
UPDATE subcategories SET expiry_value = 10, expiry_unit = 'years'
WHERE name IN (
    'SP-Dossier N°1 : Budget annuel du SPC (FEC REDAL & FEC SRM)',
    'SP-Dossier N°2 : Rapports d''activité du SPC',
    'SP-Dossier N°3 : Rapports d''audit',
    'SP-Dossier N°5 : Marchés et BCs',
    'SP-Dossier N°6 : CRQ',
    'SP-Dossier N°8 : Etats du dossier RH (SDSPD)'
);

-- 5 ans
UPDATE subcategories SET expiry_value = 5, expiry_unit = 'years'
WHERE name = 'SP-Dossier N°7 : Rapports et Fiches Visites Terrains';

-- Permanent (99 ans)
UPDATE subcategories SET expiry_value = 99, expiry_unit = 'years'
WHERE name = 'SP-Dossier N°4 : Rapports Semestriels et annuels';

-- Définitive (99 ans)
UPDATE subcategories SET expiry_value = 99, expiry_unit = 'years'
WHERE name = 'SP-Dossier N°9 : Conventions CAS';

-- ============================================================
-- SRM (33 sous-catégories)
-- ============================================================

-- Permanent (99 ans)
UPDATE subcategories SET expiry_value = 99, expiry_unit = 'years'
WHERE name IN (
    'SR-Dossier N°1 : Plan pluriannuel d''investissement (N+1 à N+5)',
    'SR-Dossier N°9 : Inventaire permanent des stocks / Inventaire physique des stocks et immobilisations',
    'SR-Dossier N°14 : Projections (N à N+5)',
    'SR-Dossier N°15 : Budget Fonctionnement de l''année N+1'
);

-- Définitive (99 ans)
UPDATE subcategories SET expiry_value = 99, expiry_unit = 'years'
WHERE name IN (
    'SR-Dossier N°13 : Schémas directeurs (eau, électricité, assainissement)',
    'SR-Dossier N°22 : Manuel des procédures comptables',
    'SR-Dossier N°30 : Liste du personnel (actualisée)',
    'SR-Dossier N°31 : Plan de formation annuel et pluriannuel',
    'SR-Dossier N°32 : Engagements sociaux et rapports aux partenaires sociaux'
);

-- 5 ans
UPDATE subcategories SET expiry_value = 5, expiry_unit = 'years'
WHERE name = 'SR-Dossier N°33 : Canevas des indicateurs de performance RH';

-- 10 ans (tous les autres SRM)
UPDATE subcategories SET expiry_value = 10, expiry_unit = 'years'
WHERE name IN (
    'SR-Dossier N°2 : Budget annuel d''investissement (N+1)',
    'SR-Dossier N°3 : KPI performances techniques (Électricité)',
    'SR-Dossier N°4 : KPI performances techniques (Eau)',
    'SR-Dossier N°5 : KPI performances techniques (Assainissement)',
    'SR-Dossier N°6 : KPI performances techniques (STEP, Séchage & réutilisation)',
    'SR-Dossier N°7 : KPI performances clientèle',
    'SR-Dossier N°8 : Suivi du patrimoine initial',
    'SR-Dossier N°10 : Indicateurs achats/ventes eau & électricité',
    'SR-Dossier N°11 : Canevas des indicateurs de performance investissement (Etat des réalisations mensuelles)',
    'SR-Dossier N°12 : Facturation des travaux réalisés par la société (Bordereau des prix : annexe 15 REDAL)',
    'SR-Dossier N°16 : Budget de trésorerie consolidé (N+1)',
    'SR-Dossier N°17 : Compte rendu financier',
    'SR-Dossier N°18 : Situations mensuelles d''exécution budgétaire (Réalisations)',
    'SR-Dossier N°19 : Comptes de produits et charges (CPC) – consolidé et par métier (Etat Comptabilité analytique)',
    'SR-Dossier N°20 : Rapport de performances financières',
    'SR-Dossier N°21 : État des soldes de gestion (ESG) – consolidé et par métier',
    'SR-Dossier N°23 : Situation mensuelle du compte FEC / Arrêté annuel du compte FEC',
    'SR-Dossier N°24 : Copie de la liasse fiscale',
    'SR-Dossier N°25 : Etats Comptabilité budgétaire',
    'SR-Dossier N°26 : Rapport Commissaire aux Comptes',
    'SR-Dossier N°27 : Liste assurances',
    'SR-Dossier N°28 : Versement des échéances dettes ONEE',
    'SR-Dossier N°29 : Convention cadre signée entre les pouvoirs publics, l''ONEE et les partenaires sociaux'
);

-- ============================================================
-- TRANSPORT (5 sous-catégories) — 10 ans confirmé client
-- ============================================================
UPDATE subcategories SET expiry_value = 10, expiry_unit = 'years'
WHERE category_id = (SELECT id FROM categories WHERE name = 'Transport');

-- ============================================================
-- VÉRIFICATION
-- ============================================================
SELECT
    c.name AS categorie,
    s.name AS sous_categorie,
    s.expiry_value,
    s.expiry_unit
FROM subcategories s
JOIN categories c ON c.id = s.category_id
ORDER BY c.id, s.id;

-- Résumé : sous-cat sans expiry défini (doit retourner 0)
SELECT COUNT(*) AS sans_expiry
FROM subcategories
WHERE expiry_value IS NULL OR expiry_value = 0;
