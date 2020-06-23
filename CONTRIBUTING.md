# Tine 2.0 - Contributing

## Write Code

You might find this wiki page helpful for setting up a development environment for Tine 2.0:
https://github.com/tine20/tine20/wiki/EN:Development-Setup

* Please make sure that there aren’t existing pull requests attempting to address the issue mentioned
* Check for related issues on the issue tracker
* Non-trivial changes should be discussed on an issue first
* Let us know you’re working on the issue (by using the
 [Kanban board](https://github.com/tine20/Tine-2.0-Open-Source-Groupware-and-CRM/projects/1))
* Develop in a topic branch, not main
* Provide useful pull request description
* Write a good description of your PR
* Link to the Github issue in the description

The default workflow for Github Pull Requests applies for Tine 2.0, too.

see https://help.github.com/articles/about-pull-requests/

### Sign the CLA

This can be done directly in the Pull Request, thanks to https://cla-assistant.io/

### Writing Tests

For PHP code, write [phpunit](https://phpunit.de/) tests (see https://github.com/tine20/Tine-2.0-Open-Source-Groupware-and-CRM/tree/main/tests/tine20)
For JavaScript code, write [karma](http://karma-runner.github.io) tests (see https://github.com/tine20/Tine-2.0-Open-Source-Groupware-and-CRM/tree/main/tests/js)

### Commit Message Guidelines

Tine 2.0 uses [Conventional Commits](https://conventionalcommits.org/) style commit messages:
 
    <type>(<scope>): MESSAGE
    
    [optional body]
    
    [optional footer]

Example:

    feature(Addressbook): adds type-ahead to some contact fields
    
    * company
    * unit
    * address data
    
    closes #289

Another:
    
    fix(Phone): improve handling of empty phone numbers

    ... when looking for the matching contact

See also the [AngularJS commit guidelines](https://github.com/angular/angular/blob/22b96b9/CONTRIBUTING.md#-commit-message-guidelines).

<type> can be one of the following:

- tweak
- hack
- fix
- feature
- build
- docs
- perf
- refactor
- style
- test
- config
- script
- text

Some example <scope>s:

- APPNAME like Addressbook, Calendar, Tasks
- Tests / Unittests
- Cli
- Import, Export
- Setup

You should reference a GitHub issue (if exists) like this:

    See #1234
    
If the commit closes an issue, it should be done like this:

    Closes #1234
    
(or any other "closing" keyword - see https://help.github.com/articles/closing-issues-using-keywords/ for reference)

If the commit reverts a previous commit, it should begin with revert:, followed by the header of the reverted commit.
In the body it should say: "This reverts commit <hash>", where the hash is the SHA of the commit being reverted.

We also use some annotations to mark commits for documentation follow-ups:

- @usermanual (User manual needs to be updated because of this commit)
- @releasenotes (RELEASENOTES need to be updated because of this commit)

## Reporting Bugs

Before submitting an issue please check that you’ve completed the following steps:

* Made sure you’re on the latest version
* Used the search feature to ensure that the bug hasn’t been reported before

Bug reports should contain the following information:

* Summary: A brief description
* Steps to reproduce: How did you encounter the bug? Instructions to reproduce it
* Expected behavior: How did you expect it to behave?
* Actual behavior: How did it actually behave?
* References: Links to any related tickets or information sources.
* If possible, add log file snippets (Tine 2.0 bog, webserver logs, browser console logs, ...)
* If possible, attach visual documentation of the bug. Screenshots, video and/or animated gifs.

## Translations

Tine 2.0 manages translations at [Transifex](https://www.transifex.net/projects/p/tine20/),
a free Web-Service for open source projects.

## Write Documentation

_TODO add more_

### Coding Standards

For PHP Code, we use the [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) and [PSR-2](https://www.php-fig.org/psr/psr-2/) Coding Standards.

## Asking and Answering Questions

If you have a question, please open an issue. We will put the label "Question" on it. Feel
free to answer questions of other users. You could also put your question on Stackoverflow (tag "tine20"):
https://stackoverflow.com/questions/tagged/tine20

Questions on GitHub can be asked in german or english.

## Additional Resources

* [Guidelines for CONTRIBUTING](https://help.github.com/articles/setting-guidelines-for-repository-contributors/)
* [AngularJS Commit Message Guidelines](https://github.com/angular/angular/blob/22b96b9/CONTRIBUTING.md#-commit-message-guidelines)
