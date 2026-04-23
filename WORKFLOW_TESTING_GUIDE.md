# 📋 Guide de Test du Workflow d'Approbation en Cascade

## ✅ Vérifications Préalables

### 1. Vérifier que les migrations sont appliquées
```bash
php artisan migrate:status
```

Si les nouvelles migrations ne sont pas appliquées :
```bash
php artisan migrate
```

### 2. Vérifier qu'il n'y a pas d'erreurs de syntaxe
```bash
php artisan route:list | grep workflow
php artisan config:clear
php artisan cache:clear
```

## 🧪 Test Manuel dans le Navigateur

### Étape 1 : Créer des Règles de Workflow

1. **Connectez-vous** avec un compte administrateur

2. **Accédez à la gestion des workflows** :
   - Menu latéral → **Structures** (ou Departments)
   - Cliquez sur une structure existante
   - Cliquez sur **"Règles de workflow"**
   - URL : `http://votre-domaine/workflow-rules/by-department/{department_id}`

3. **Créer une règle multi-niveaux** :
   
   **Exemple : Workflow à 2 niveaux**
   - **Catégorie** : Sélectionnez une catégorie ou laissez vide pour "Toutes"
   - **Statut de départ** : `pending`
   - **Statut d'arrivée** : `approved`
   
   **Niveau 1** :
   - Type d'approbateur : `Par rôle`
   - Rôle : Sélectionnez un rôle (ex: "Chef de service")
   
   **Cliquez sur "+ Ajouter un niveau"**
   
   **Niveau 2** :
   - Type d'approbateur : `Par utilisateur spécifique`
   - Utilisateur : Sélectionnez un utilisateur (ex: "Directeur")
   
   **Cliquez sur "Enregistrer"**

4. **Vérifier que la règle apparaît** dans la liste des règles existantes

### Étape 2 : Créer un Document Test

1. **Accédez à la page de téléversement** :
   - Menu → **Téléverser des documents**
   - URL : `http://votre-domaine/upload`

2. **Remplissez le formulaire** :
   - Sélectionnez un fichier
   - **Structure** : Choisissez la même structure que celle où vous avez créé la règle
   - **Service** : Sélectionnez un service
   - **Catégorie** : Sélectionnez la catégorie correspondant à votre règle (ou n'importe laquelle si règle globale)
   - Remplissez les autres champs obligatoires

3. **Soumettez le document**

4. **Vérifiez** :
   - Le document devrait avoir le statut `pending`
   - Une notification devrait être envoyée aux approbateurs du niveau 1

### Étape 3 : Visualiser la Progression du Workflow

1. **Accédez au document** :
   - Menu → **Documents**
   - Cliquez sur le document que vous venez de créer
   - Ou allez dans **Documents en attente**

2. **Vérifiez l'affichage de la progression** :
   - Vous devriez voir un encadré **"Progression du Workflow"**
   - Badge indiquant **"Étape 1/2"** (si 2 niveaux)
   - Liste des niveaux avec leur statut :
     - ⏰ Niveau 1 : En attente de validation
     - ⚪ Niveau 2 : (pas encore activé)

### Étape 4 : Approuver le Niveau 1

1. **Connectez-vous** avec un compte ayant le rôle défini au niveau 1

2. **Accédez au document en attente** :
   - Menu → **Documents en attente** (ou **Approbations**)
   - Cliquez sur le document

3. **Approuvez le document** :
   - Cliquez sur le bouton **"Approuver"**
   - (Optionnel) Ajoutez un commentaire
   - Confirmez

4. **Vérifiez** :
   - Le niveau 1 devrait être marqué comme ✅ **Approuvé**
   - Le niveau 2 devrait maintenant être ⏰ **En attente**
   - Badge devrait indiquer **"Étape 2/2"**
   - Une notification devrait être envoyée aux approbateurs du niveau 2

### Étape 5 : Approuver le Niveau 2 (Final)

1. **Connectez-vous** avec le compte utilisateur défini au niveau 2

2. **Accédez au document** et **approuvez-le**

3. **Vérifiez** :
   - Le document devrait maintenant avoir le statut `approved`
   - Les deux niveaux devraient être marqués comme ✅ **Approuvés**
   - Le document n'apparaît plus dans "Documents en attente"
   - Le document apparaît dans "Documents approuvés"

### Étape 6 : Tester le Refus

1. **Créez un nouveau document** (même processus qu'à l'étape 2)

2. **Connectez-vous** avec un approbateur du niveau 1

3. **Refusez le document** :
   - Cliquez sur **"Refuser"**
   - Ajoutez un motif de refus
   - Confirmez

4. **Vérifiez** :
   - Le document devrait avoir le statut `declined`
   - Le workflow devrait s'arrêter (pas de passage au niveau 2)
   - Le document n'apparaît plus dans "Documents en attente"

## 🔍 Points de Vérification

### Base de données

Vérifiez les tables suivantes :

```sql
-- Vérifier les règles créées
SELECT * FROM workflow_rules WHERE department_id = {votre_department_id};

-- Vérifier les approbations d'un document
SELECT * FROM document_approvals WHERE document_id = {votre_document_id};

-- Vérifier le statut du document
SELECT id, title, status FROM documents WHERE id = {votre_document_id};
```

### Logs

Vérifiez les logs Laravel pour détecter d'éventuelles erreurs :
```bash
tail -f storage/logs/laravel.log
```

## 🐛 Dépannage

### Le workflow ne s'initialise pas

**Problème** : Le document est créé mais aucune approbation n'est créée.

**Solutions** :
1. Vérifiez qu'une règle existe pour ce département/catégorie avec `level = 1`
2. Vérifiez que la règle a `from_status = 'pending'` et `to_status = 'approved'`
3. Vérifiez les logs : `tail -f storage/logs/laravel.log`

### L'utilisateur ne peut pas approuver

**Problème** : Le bouton "Approuver" n'apparaît pas ou retourne une erreur.

**Solutions** :
1. Vérifiez que l'utilisateur a la permission `approve document`
2. Vérifiez que l'utilisateur fait partie des approbateurs du niveau actuel :
   - Si règle par rôle : l'utilisateur doit avoir ce rôle
   - Si règle par utilisateur : l'utilisateur doit être celui spécifié
3. Vérifiez que le document est bien au statut `pending`

### La progression ne s'affiche pas

**Problème** : Le composant de progression n'apparaît pas.

**Solutions** :
1. Vérifiez que le document a des approbations : `SELECT * FROM document_approvals WHERE document_id = X`
2. Vérifiez que le composant est bien inclus dans la vue
3. Videz le cache : `php artisan view:clear`

## 📊 Scénarios de Test Avancés

### Test 1 : Workflow à 3 niveaux
- Créez une règle avec 3 niveaux
- Testez l'approbation séquentielle de chaque niveau

### Test 2 : Règles par catégorie
- Créez 2 règles différentes pour 2 catégories différentes
- Vérifiez que chaque document suit la bonne règle

### Test 3 : Règle globale vs spécifique
- Créez une règle globale (sans catégorie)
- Créez une règle spécifique pour une catégorie
- Vérifiez que la règle spécifique a la priorité

### Test 4 : Notifications
- Vérifiez que les notifications sont envoyées à chaque niveau
- Vérifiez le contenu des notifications

## ✅ Checklist de Validation

- [ ] Les règles de workflow se créent correctement
- [ ] Le workflow s'initialise lors de la création d'un document
- [ ] La progression s'affiche correctement
- [ ] L'approbation niveau 1 fonctionne
- [ ] Le passage au niveau 2 est automatique
- [ ] L'approbation niveau 2 finalise le document
- [ ] Le refus arrête le workflow
- [ ] Les notifications sont envoyées
- [ ] Les permissions sont respectées
- [ ] L'historique est enregistré

## 🎯 Résultat Attendu

Un système de workflow complet où :
1. Les administrateurs peuvent configurer des workflows multi-niveaux
2. Les documents suivent automatiquement le workflow défini
3. Les approbateurs reçoivent des notifications
4. La progression est visible en temps réel
5. Le système gère les approbations et refus de manière sécurisée
