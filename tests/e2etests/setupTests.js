global.afterEach(async () => {
    if (jasmine.currentTest.failedExpectations.length > 0) {
        const pages = await browser.pages();
        for (let i = 1; i < pages.length; i++) {
            await pages[i].screenshot({path: 'screenshots/Error/' + jasmine.currentTest.description + i.toString() + '.png'});
        }
    }
});