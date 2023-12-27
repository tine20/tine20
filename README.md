TL;DR
=====
This project got renamed to _tine-groupware_. Therefore, we also moved the repo to a new [namespace](https://github.com/tine-groupware/tine).

A lot of details and background information about this step can be found in this [podcast episode](https://www.tine-groupware.de/podcast/16-abschied-von-der-community-edition/) (german).

Tine 2.0 got renamed to tine-groupware
======================================
As much as we liked our old name `Tine 2.0` we noted that the `2.0` badge got lame. 16 years ago our technical approach was new and fancy, some call such stuff disruptive innovation and that's what the `2.0` badge was about.

But nowadays open source business software, automated testing and building rich clients in javascript is more or less industry standard.

Moreover, end users got confused and mixed up the `2.0` with version numbering.

One product line, everything is published
=========================================
Until version `2022.11` we maintained two branches. On the one hand we maintained the so-called _community edition_ which was published as `main` branch here. Additionally, we maintained our _business edition_ with enhanced stability and more features which was not published in public.

The old _community deal_ was (quoting our README from 2015-12):
> Together with our great community of open source addicted developers and testers, we build new features, try out exciting concepts and drive innovation within the tine Community Edition. As a user you become a member of this community and we expect you to support innovation by creating code, reporting bugs or improving the documentation.
>
> With the tine Community Edition you are always provided with the latest additions. For the sake of innovation we don't fix bugs or supply security updates for old versions.

This deal didn't work well. The _community edition_ was unstable on purpose! But with this approach we damaged our product name as it got installed in business contexts a lot. 

Also, this [free (as in beer)](https://www.howtogeek.com/31717/what-do-the-phrases-free-speech-vs.-free-beer-really-mean/) usage was not our intention for creating tine-groupware as free and open source software. The deal we want to promote is: Help tine-groupware to grow by actively participating with writing code or documentation, helping out with issues or contributing money.

Last but not least, maintaining two branches was time-consuming, cumbersome and error-prone. 

As of `2023.11` we drastically easy our product line with maintaining the `business edition` branch only and releasing all our code and features in public. 


Pull requests, discussions and issues are not moved
===================================================
Open pull requests and discussions will we process here and fade out over time. Please start new stuff in the new repo.

Old issues will to be processed or migrated. Over time the tracker became a pile of shit, and we are not in the position to fix that situation. With your help we hope to do better in the new repo. If you have a valid topic, please create a fresh issue in the new repo if you are willing to work on it with us.

Wiki is discontinued
====================
As you properly know, we don't like wikis - but we do like git and code 😆. Therefore, documentation moved straight into our [git repo](https://github.com/tine-groupware/tine/tree/main/docs). We use the fabulous [mkdocs](https://www.mkdocs.org) project as foundation. The workflow is fully integrated into our [dev setup,](https://github.com/tine-groupware/tine-dev) so you can start right away to write great content utilizing all resources from withing the repo.

A nightly version is published to https://tine-docu.s3web.rz1.metaways.net

Migration paths
===============
To migrate from the discontinued _community edition_ to the [_business edition development setup_](https://github.com/tine-groupware/tine-dev) you first need to install the latest version of the _community edition_ which can be found [here](https://github.com/tine20/tine20/releases/tag/2023.12.1). Otherwise, migration scipts in the development setup won't work. Please make sure to keep your version current, otherwise migrations might fail in the future.




