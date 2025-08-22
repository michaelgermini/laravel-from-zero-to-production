# Laravel Book Website

Un site web complet pour le livre "Laravel: From Zero to Production" avec conversion automatique Markdown vers HTML.

## 🚀 Fonctionnalités

### ✅ **Pages HTML Générées Automatiquement**
- **15 chapitres** avec contenu Markdown converti en HTML
- **5 projets** avec descriptions détaillées
- **Navigation complète** entre toutes les pages
- **Liens vers les fichiers Markdown originaux**

### ✅ **Conversion Markdown → HTML**
- Conversion automatique des fichiers `.md` en pages HTML
- Mise en forme CSS personnalisée
- Coloration syntaxique pour les blocs de code
- Support des tableaux, listes, et citations

### ✅ **Interface Utilisateur**
- Design responsive avec Bootstrap 5
- Navigation fluide avec breadcrumbs
- Boutons de navigation (Précédent/Suivant)
- Animations et interactions JavaScript
- Mode sombre/clair

## 📁 Structure des Fichiers

```
website/
├── index.html              # Page d'accueil
├── styles.css              # Styles CSS personnalisés
├── script.js               # JavaScript interactif
├── generate-all-pages.py   # Script de génération automatique
├── chapters/               # Pages des chapitres
│   ├── index.html         # Index des chapitres
│   ├── 01-introduction.html
│   ├── 02-installation.html
│   └── ... (15 chapitres)
├── projects/              # Pages des projets
│   ├── index.html         # Index des projets
│   ├── todo-app.html
│   ├── blog-platform.html
│   └── ... (5 projets)
└── README.md              # Ce fichier
```

## 🛠️ Utilisation

### 1. **Démarrer le serveur local**
```bash
cd website
python -m http.server 8000
```

### 2. **Accéder au site**
- **Page d'accueil**: http://localhost:8000/website/
- **Chapitres**: http://localhost:8000/website/chapters/
- **Projets**: http://localhost:8000/website/projects/

### 3. **Regénérer les pages**
```bash
python generate-all-pages.py
```

## 📚 Chapitres Disponibles

### Partie I: Fondamentaux
1. **Introduction** - Vue d'ensemble de Laravel
2. **Installation** - Configuration de l'environnement
3. **Routing** - Système de routage
4. **Controllers** - Gestion des contrôleurs
5. **Blade Templates** - Moteur de templates
6. **Eloquent ORM** - Opérations de base de données

### Partie II: Construction d'Applications
7. **Migrations** - Gestion du schéma de base de données
8. **Middleware** - Filtrage des requêtes HTTP
9. **Authentication** - Authentification et autorisation
10. **Events & Queues** - Gestion des événements et tâches en arrière-plan
11. **Testing** - Écriture de tests
12. **Deployment** - Déploiement en production

### Partie III: Sujets Avancés
13. **Caching** - Optimisation des performances avec le cache
14. **Performance** - Optimisation des performances
15. **Microservices** - Architecture microservices

## 🎯 Projets Disponibles

### Niveau Débutant
- **Todo Application** - Opérations CRUD de base

### Niveau Intermédiaire
- **Blog Platform** - Gestion de contenu avec authentification
- **REST API** - Construction d'APIs RESTful

### Niveau Avancé
- **E-commerce Shop** - Boutique en ligne avec intégration de paiement
- **Multi-Step Wizard** - Formulaire multi-étapes complexe

## 🔧 Personnalisation

### Modifier le Style
Éditez `styles.css` pour personnaliser l'apparence :
```css
/* Exemple de personnalisation */
.text-gradient {
    background: linear-gradient(45deg, #ff2d20, #636b6f);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
```

### Ajouter de Nouvelles Pages
1. Ajoutez le fichier Markdown dans `../docs/`
2. Modifiez le script `generate-all-pages.py`
3. Exécutez le script pour générer la nouvelle page

### Modifier la Navigation
Éditez les fichiers HTML générés ou modifiez les templates dans le script Python.

## 🌐 Déploiement

### GitHub Pages
```bash
# Cloner le repository
git clone <repository-url>
cd website

# Générer les pages
python generate-all-pages.py

# Déployer
git add .
git commit -m "Update website"
git push origin main
```

### Netlify/Vercel
1. Connectez votre repository GitHub
2. Configurez le dossier de build : `website`
3. Déployez automatiquement

## 📝 Scripts Disponibles

### `generate-all-pages.py`
Script principal qui :
- Convertit tous les fichiers Markdown en HTML
- Génère les pages des chapitres et projets
- Ajoute la navigation et les liens
- Applique le style CSS personnalisé

### `generate-pages-enhanced.py`
Version de test avec quelques chapitres pour vérifier la conversion.

## 🎨 Fonctionnalités Avancées

### Coloration Syntaxique
Utilise Prism.js pour la coloration syntaxique des blocs de code :
```html
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
```

### Navigation Intelligente
- Breadcrumbs pour la navigation
- Boutons Précédent/Suivant automatiques
- Liens vers les fichiers Markdown originaux

### Responsive Design
- Compatible mobile et desktop
- Navigation adaptative
- Images et contenu responsifs

## 🐛 Dépannage

### Erreur 404 sur favicon.ico
Créé un fichier `favicon.ico` vide pour éviter les erreurs.

### Pages non générées
Vérifiez que les fichiers Markdown existent dans `../docs/` et exécutez :
```bash
python generate-all-pages.py
```

### Problèmes de style
Vérifiez que tous les fichiers CSS et JavaScript sont chargés correctement.

## 📞 Support

Pour toute question ou problème :
1. Vérifiez ce README
2. Consultez les logs du script de génération
3. Vérifiez la console du navigateur pour les erreurs JavaScript

---

**Laravel: From Zero to Production** - Site web complet avec conversion Markdown automatique 🚀
