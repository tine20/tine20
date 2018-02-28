translations doku
-----------------

prerequisites

    $ sudo apt install php-xml
    $ sudo pip install transifex-client

po-merge-helper aktivieren (eintrag in .git/config)

    [merge "pofile"]
        name = merge po-files driver
        driver = ./scripts/merge-po-files %A %O %B
        recursive = binary

tx-pull - holt die aktuellen übersetzungen von transi

(vor am besten neuen branch auschecken)

    $ vendor/bin/phing tx-pull

git add

    $ git add */translations

lokale (bzw. DE) deutsche übersetzungen wiederherstellen
TODO: evtl bekommen wir die de translations auch gemerged?

    $ git reset HEAD */translations/de.po 
    $ tx push -t -l de

eventuell muss man force pushen, da online eine "neuere" version ist

    $ tx push -t --force -l de  

commit + push to git

    $ git commit -m "updates translations"
    $ git add */translations
    $ git commit -m "backup de translations"    

neue strings zu transi pushen

    $ vendor/bin/phing tx-push


--> im wiki aktualisieren
