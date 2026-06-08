# Formation Client — ERP Menuiserie TAAOUATI

Guide de formation (2-3 heures) pour la livraison du système.

---

## Accès au système

| Rôle | Email | Usage |
|------|-------|-------|
| **Admin** | `admin@taaouati.com` | Tout : stats, paramètres, utilisateurs |
| **Caisse** | `caisse@taaouati.com` | POS, clients, commandes |
| **Atelier** | `atelier@taaouati.com` | File d'attente mobile uniquement |

> Mots de passe initiaux : voir tableau remis à la livraison. **Changez-les après la 1ère connexion.**

**URL :** `http://13-140-140-149.sslip.io` (ou votre domaine)

---

## Session 1 — Point de Vente (45 min)

### Objectif
Vendre panneaux, bandchant et services, imprimer facture, envoyer atelier.

### Étapes
1. Aller dans **Caisse (POS)**
2. Sélectionner un **client** (ou créer nouveau)
3. Ajouter articles :
   - **Panneau MDF** → quantité en pièces
   - **Bandchant** → quantité en mètres
   - **Service** (découpe, etc.)
4. Pour bandchant : activer **Collage de chant** si besoin (+ tarif DH/m)
5. Cocher **Atelier** si travail à faire
6. Attacher **Plan SketchCut (PDF)** si découpe
7. Saisir **avance** (cash) ou laisser 0 pour crédit client
8. Cliquer **VALIDER**
9. Facture s'imprime automatiquement

### À retenir
- Le stock se déduit automatiquement
- L'avance réduit le crédit client
- Le PDF SketchCut apparaît dans l'atelier

---

## Session 2 — Stock & Achats (30 min)

### Stock MDF / Bandchant
1. **Stock MDF** → voir quantités, couleurs, épaisseurs
2. **Stock Bandchant** → longueur restante en mètres
3. **Entrée stock rapide** → ajouter sans achat fournisseur

### Achats fournisseurs
1. **Achats** → nouveau bon de réception
2. Sélectionner fournisseur + articles
3. Le stock et la dette fournisseur se mettent à jour

### Casse / Ajustement
1. **Stock** → bouton ajustement
2. Choisir raison : kosor, chute, erreur

---

## Session 3 — Factures & Clients (30 min)

### Créer une facture manuelle
1. **Factures** → Nouveau document
2. Choisir client, type (Facture / Devis)
3. Ajouter articles : **Depuis Stock** ou **Libre**
4. Valider → stock déduit + dette client

### Ajouter articles à une facture existante
1. **Historique Commandes** → ouvrir facture
2. **Ajouter articles**
3. Pour bandchant : toggle **Collage de chant**

### Envoyer facture WhatsApp
1. Cliquer icône **WhatsApp** sur facture
2. Le message contient le **lien PDF direct**
3. Le client clique et télécharge

### Dossier client
1. **Clients** → cliquer sur client
2. Voir crédit, historique, paiements

---

## Session 4 — Atelier (30 min)

### File d'attente (Admin)
1. **Atelier** → voir jobs en attente
2. Cliquer **PDF** pour voir plan SketchCut
3. Marquer services terminés
4. **Livrer** quand tout est fini

### Vue ouvrier (Mobile)
1. Se connecter avec compte **atelier**
2. Voir jobs assignés
3. Cocher tâches terminées

### Mode Express (Urgent)
- Cliquer éclair ⚡ sur un job → priorité haute

---

## Session 5 — Administration (20 min)

### Paramètres entreprise
1. **Paramètres** → remplir :
   - Nom atelier, téléphone, adresse
   - ICE, RC (pour factures)
   - Logo (JPG/PNG)
   - Texte bas de facture

### Utilisateurs
1. **Utilisateurs** → créer caissier / ouvrier
2. Rôles : admin, cashier, worker

### Statistiques
1. **Dashboard** → vue d'ensemble
2. **Stats Financières** → revenue, marge, OPEX

### Sauvegarde
1. **Backup** → lancer sauvegarde manuelle
2. Sauvegarde auto : chaque nuit à 01h30

---

## Checklist Go Live (client)

- [ ] Paramètres remplis (logo, ICE, téléphone)
- [ ] Stock initial entré (MDF + bandchant)
- [ ] Services configurés (découpe, collage)
- [ ] Clients principaux ajoutés
- [ ] Mots de passe changés
- [ ] Test POS complet fait
- [ ] Test atelier + PDF fait
- [ ] Test WhatsApp facture fait

---

## Support

| Problème | Solution |
|----------|----------|
| Page blanche | Vider cache navigateur (Ctrl+F5) |
| PDF ne s'ouvre pas | Re-uploader depuis POS |
| Stock incorrect | Vérifier achats / ajustements |
| Mot de passe oublié | Contacter support |

**Support technique :** [votre téléphone / WhatsApp]

---

*Document généré pour livraison client TAAOUATI — ERP Menuiserie*
