# Utilisation  

**Création / Gestion des utilisateurs:**

l'application propose une procédure d'inscription : "sign up"

  ![Alt text](/Img_doc/signup.png?raw=true)

Celle-ci permet de faire son inscription avec validation de la demande par token mail, un mail de création est envoyé aux administrateurs de l'application.

Une fois votre compte créé, il doit être activé par un administrateur qui a été précedement notifié.
Le référent du projet accepte ensuite votre demande de rejoindre le projet.
Une fois cela effectué, vous pouvez déposer des jeux de données sur la plateforme.

**Ajout d'un nouveau jeu de données:**

Les utilisateurs ayant le statut Feeder et referent peuvent effectuer cette action.

Aller dans l'onglet : "Upload"

L'utilisateur rempli le formulaire, il rempli les champs obligatoire (marqués d'une étoile rouge), la vérification est faite coté client et coté serveur.

Les informations sont ensuite traitées et insérées en base de données.

**Suppression d'un jeu de données:**

Un jeu de données peut être uniquement supprimé par le referent, s'il n'as pas été déja validé.



**Fonctionnement:**

Le frontend effectue un appel AJAX qui va se connecter au backend de l'application qui se connecte à l'api elasticsearch.
Il est possible de trier les resultats obtenu avec des filtres ( lithology , date, mesure).

Ces filtres sont aussi applicable sur une zone de la map defini, pour cela il faut maintenir "CTRL" et choisir un lieux, lorsque que vous relacherez ,
la map s'actualisera.

En selectionnant un point, vous avez accées aux metadonnées, aux données,au données brute, ainsi que des images.
Il est possible de les consulter en ligne ou de les telecharger.



