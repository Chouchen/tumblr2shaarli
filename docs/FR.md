# Installer et utiliser ce script

  1. Copier le contenu de `src` dans votre instance Shaarli (à sa racine où il y a `index.php`)
  2. Copier `tumblr.ini.dev` vers `tumblr.ini`
  3. Remplir les variables de `tumblr.ini`

```ini
tumblr = xxx.tumblr.com # l'URL de votre tumblr (ça peut être un autre domaine si vous avez payé)
api_key = xxx # votre clef d'API tumblr (voir ci-dessous)
private = true # indique si les liens importés seront privés ou publiques
shaarli_dir = # laisser vide si vous avez suivi ce tuto, sinon, le chemin vers votre instance shaarli
```
  4. Vérifier si vous avez les droits en écriture sur la base (datastore.php) et le dossier ou se trouve `import.php`
  5. Lancer le script via votre navigateur ou par ligne de commande

### La clef d'API Tumblr

Vous avez besoin d'enrigistrer une app Tumblr :
  
  1. Connectez vous sur votre compte tumblr
  2. Allez sur https://www.tumblr.com/oauth/apps
  3. Cliquez sur `Enregistrer une application`
  4. Remplissez tous les champs obligatoires + `website` (même si c'est pas marqué obligatoire, ça l'est)
  5. Vous l'avez votre clef ! **Clé du client (OAuth)** !
  6. Astuce : vous pouvez la retrouver ici : https://www.tumblr.com/settings/apps


### Limitations 

L'API de Tumblr ne peut récupérer *que* 20 × 250 posts (J'ai calculé, ça fait 5000)

Ce script ne permet pas de *compléter* un import depuis un post précis, donc si vous avez plus de 5k posts, ça ne fonctionnera pas (enfin, vous n'aurez pas tout)
