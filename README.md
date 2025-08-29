Nouveau Projet:

-   symfony new NomProjet
-   composer require symfony/maker-bundle --dev
-   composer require orm

Table et entités:

-   Parametrer .env.local pour link la DB (ex WAMP : DATABASE_URL="mysql://root:@127.0.0.1:3306/EcoGardenApi?serverVersion=9.1.0&charset=utf8mb4")
-   Créer la base de donnée : symfony console doctrine:database:create -–if-not-exists
-   Créer une entité : symfony console make:entity Conseil (ressaisir pour remodifier l'entité)
-   composer require symfony/validator
-   Utiliser #[ORM\HasLifecycleCallbacks] pour gérer les updates et creates dates
-   Créer la migration : symfony console make:migration
-   Appliquer la migration : symfony console doctrine:migrations:migrate

Fixtures:

-   composer require orm-fixtures --dev
-   php bin/console doctrine:fixtures:load (appliquer la fixture)

Premieres routes:

-   php bin/console make:controller
-   composer require symfony/serializer-pack
