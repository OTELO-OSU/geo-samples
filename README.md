# 🌌 GeoSamples

## ☝️ Prérequis

- 🍃 Un conteneur mongo en 3.4 actif nommé "GLOBAL_mongo" sur le network nommé "geosamples.net"
- 🐬 Un conteneur MySQL actif nommé "GLOBAL_mysql" sur le network nommé "geosamples.net"

## 🧪 Utilisation

Cloner le repository github (utilisation de SSH) :
```sh
git clone git@github.com:OTELO-OSU/geo-samples.git
```

Entrer dans le dossier :
```sh
cd geo-samples
```

Changer de branche pour aller sur la main :
```sh
git switch main
```

Renommer le config.example.ini en config.ini :
```sh
mv config.example.ini config.ini
```

Changer les variables du config.ini :
```yaml
# Username du user de la base Mongo
MONGO_INIT_DB_USER=

# Password du user de la base Mongo
MONGO_INIT_DB_USER_PASS=

# Nom de la base Mongo
MONGO_DATABASE=

# Nom de la collection Mongo
COLLECTION_NAME=

# Chemin vers le dossier d'import sur la machine
IMPORT_FOLDER=

# Email du noreply
NO_REPLY_MAIL=

# SMTP
SMTP=
```

Renommer le AuthDB.example.ini en AuthDB.ini :
```sh
mv AuthDB.example.ini AuthDB.ini
```

Changer les variables du config.ini :
```yaml
driver=mysql
host=GLOBAL_mysql

# Nom de la base de données MySQL
database=

# Username du user de la base MySQL
username=

# Password du user de la base MySQL
password=

charset=utf8
collation=utf8_unicode_ci
```

Build et lancer l'image :
```sh
# Build en tant que sitegeosmpl
docker build -t sitegeosmpl -f ./docker/geo-samples .

# Lancer le conteneur en exposant le port voulu
docker run -d --restart unless-stopped -p XXXX:80 --network geosamples.net sitegeosmpl
```