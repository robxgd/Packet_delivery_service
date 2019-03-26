# Crowdsourced pakket levering

Auteurs: Arno Heirman en Rob Hofman
Datum: 20/12/2018   
Opleiding: Master of Science in de industriële wetenschappen: elektronica-ICT  
Vak: Ingebedde systemen: algoritmes

## Instructies

1. importeer de sql dump (project3 )op in een databank project met een gebruiker project3 en wachtwoord project3
2. surf naar de login.php pagina. (indien je naar index gaat en niet ingelogd bent wordt je geredirect)
3. log in met gebruiker rob en wachtwoord test
4. u kan een straat of nummer in geven en starten met leveren
5. eens u in de buurt van een lever adres bent verschijnt de delivered knop. 
6. als alle pakketjes geleverd zijn kan u finnish klikken

## structuur

* API (hier zitten alle php files die als api call worden aangeroepen)
* config (folder voor de database connectie)
* functions (server side functies)
* models (klasses die als models dienen)
* root (hier staan de views die in browser worden weergegeven. dit zijn de paginas waar de gebruiker naartoe surft. Ook staat de client side javascript hier en de css)