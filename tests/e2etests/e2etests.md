# End-2-ende Tests

## Was ist ein End-2-end Test

 - auch Broad-Stack-Test / Full-Stack-Tests
 - soll Workflows aus der Endnutzersicht überprüfen
 - hoher Komplexität, Fehleranfälligkeit und hohem Wartungsaufwand
 - erst zum Ende des Testszyklus
 - realitätsnahme Testumgebung
 - Am besten User Case ausdenken und nachbauen
 ### Best Practices 
    Wie ein Endnutzer denken.
    Happy Path / Golden Paths - Typische Szenarios
    Für Risikoanalyse - Wichtige bestandetteil der Software
    Testumgungen effizient aufsetzten - Dokumentieren!
    
## Was ist puppeteer?
    
    Node library
    Steuerung von Chrome/Chromium über das DevTool-Protokoll
    Meisten sachen die man Händisch macht kann mab über Puppeteer machen lassen
    - Screenshots
    - Single-Page-Applications
    - Server-Side Rendering
    - formulare, UI-Test, Eingabe...
    Zeitleisten-Trace für optimierung
   
## Testumgebung

Benötigt werden folgen Pakete

    "dotenv": "^8.2.0",
    "expect-puppeteer": "^4.3.0",
    "jest": "^24.9.0",
    "jest-puppeteer": "^4.3.0",
    "puppeteer": "^2.0.0"


- puppeteer -> ist das Hauptpaket
- dotenv -> für umgebungsvariablen
- jest / jest-puppeteer -> stellt die testumgebunge bereit
- expect-Puppeter -> hilfsfunktionen für eine stellen in der oberfläche
- Idee env für Worker -> mehrer Testfiles gleichzeitg abarbeiten

### Jenkins

Jenkins ist die Testplattform. Sie stellt ein System bereit wo über ein git pull der gewünschte Stand
des Codes gezogen wird. Es wird immer eine "frisches System" benutzt.

- Npm install für Tine
- start Webpack
- Install Demodaten
- npm install für e2etests
- benötige pakete installieren
      
      sudo apt install gconf-service libasound2 libatk1.0-0 libatk-bridge2.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release xdg-utils wget

# Struktur
### Dateeinstruktur

- Wie bei den Unittest.
     
        Normale test:
        src/tests/{Modul}/{Modul}.test.js
        special test:
        src/special/{name}.test.js
- testFiles für Screenshots sind in src/testsScreenshots
- für helper functions src/lib/...
### Testaufbau


    const expect = require('expect-puppeteer');
    const lib = require('../../lib/browser');
    
    require('dotenv').config();
    /* Import hilfs funktionen etc.. */
    
    
    /**
    * Wird immer vor allen Test ausgeführt
    * ansich wie ein Setup für die folgen test.
    **/
    beforeAll(async () => {
        //expect.setDefaultOptions({timeout: 1000});
        await lib.getBrowser('Admin'); -> öffnet ein Browser
        und geht gleich in das Modul Admin!
    });
Überlegt hab ich mir folgende Struktur. Da es sonst unübersichtlich werden kann(Wartbarkeit)
        
        
    describe('Mainpage', () => {
        Das ist die Hauptseite jeder App. Mit dem Default Modul.
        Da kann man jetzt alle Test laufen lassen die mit dem Grid,
        WestPanel, Filterbar, etc zutun haben.
        
        Mit unterpunkten kann man dies noch besser Glieder
        z.b.
        
        describe('ContextMenu WestPanel', () => {
            hier kommen dann die test um ContextMenu vom WestPanel rein
            z.b. 
            test('open contextMenu favorite. async () => {
            bla bla bla
                }
            }
           ...
    });
    
    describe('EditDialog', () => {
        Als nächstes die EditDialog
        bei mehrern Modulen gibt es auch verschieden EditDialoge
        z.b. 
        describe('Contact', () => {
            test('add Contact', async () => {
            ...
            }
        }
        
        describe('List', () => {
            test('add List', async () => {
            ...
            }
        }        
            
    });
    
    afterAll(async () => {
        wird nach allen Test durchgeführt
        browser.close();
    });
    
## Finden der Richtigen Css selectoren etc...

    - Entwicklerkonsole offen haben
    - durch das Selector-Wergzeug die Css Klasse finden
    - (ich) prüfen ob es mehrer elemente mit der Css Klasse gibt
        * Wenn nicht kann er so verwendet werden
        * Wenn ja ( meistens bei Buttons etc die in verschieden modulen gleich sind)
          gucken das man den Selector noch genauer beschreiben kann
    - testen -> ist leider bissel try and error
    - erstmal leiber mehr mit waitFor() arbeiten. Da kann man eine Zeit in ms oder ein Selector angeben
    - Manche stellen muss man Kreativ sein... z.b. beim Combo Feldern. Die Elemente zum ausklappen können öfters vorkommen
    - Aufpassen auf Visible ! Da Selectoren auch da sein können aber nicht sichtbar sind,
      z.b. wenn mehrer module offen sind
    
## Wichtige Funktionen
    
Klicken von Elementen:

     await newPage.click('.ext-ux-grid-gridviewmenuplugin-menuBtn.x-grid3-hd-btn');
     - sucht nach dem Selector
     
     await expect(newPage).toClick('span' , {text: 'Rolle'});
     - sucht nach einem Selector + zusatz 
     
     let arrowtrigger = await newPage.$$('.x-form-arrow-trigger');
Value eines Element wiedergeben:

    let elementValue = await newPage.evaluate(() => document.querySelector("input[name=container_id]").value);

Warten auf ein Selector oder so:

    await expect(newPage).toMatchElement('.x-combo-list-item ', {text: 'Freiwillig'});
    await newPage.waitForSelector('.x-grid3-cell-inner.x-grid3-col-role');

Helper Functions:

    getCurrenUser: function (page) {
    return page.evaluate(() => Tine.Tinebase.registry.get('currentAccount'));  
    
    getElement: function (type, page, text) {
         return page.$x("//" + type + "[contains(., '" + text + "')]");
     },  
     
Doku von Puppeteer & Expect: 

    https://github.com/puppeteer/puppeteer/blob/master/docs/api.md
    https://www.npmjs.com/package/expect-puppeteer
    
## Jest Config

Configfile ist jest.config.js

    require('dotenv').config();
    
    module.exports = {
        globals: {
            browser: '',
            page: '',
            app: '',
        },
        testMatch: [
            "**/" + process.env.TEST_DIR + "/**/*.test.js"
        ],
        verbose: true,
        maxWorkers: process.env.TEST_WORKER,
        testTimeout: 60000,
    };
    
    https://jestjs.io/docs/en/configuration