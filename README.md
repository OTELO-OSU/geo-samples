# petrophysics
Petrophysics database and geographical representation

**Fonctionnement:**
Le frontend effectue un appel AJAX qui va se connecter au backend de l'application qui se connecte à l'api elasticsearch.
Il est possible de trier les resultats obtenu avec des filtres ( lithology , date, mesure).

Ces filtres sont aussi applicable sur une zone de la map defini, pour cela il faut maintenir "CTRL" et choisir un lieux, lorsque que vous relacherez ,
la map s'actualisera.

En selectionnant un point, vous avez accées aux metadonnées, aux données, ainsi que des images.
Il est possible de les consulter en ligne ou de les telecharger.

**Configuration:**

Le fichier de configuration se trouve dans Backend/config.ini

  #ELASTICSEARCH CONFIG
  ESHOST=localhost
  ESPORT=9200
  #BDD NAME
  INDEX_NAME=ordar
  CSV_FOLDER="/data/applis/ORDaR/Uploads/"
