# LiveTree

Live-updating, interactive, responsive visual tree

Try it: [https://livetree.filipe.knoedt.net/](https://livetree.filipe.knoedt.net/)

## Introduction

This is a sample project I've created as a challenge for a company based in North Carolina by November 2017. The task was interesting so I made it broader - like creating from scratch a micro built-in ORM - to use as a full stack sample app.

## Challenge

The task was to build a client side tree with different rules for it's architecture. Every user should see and interact with the same server side database persisted tree, and every update in the tree, by any user, should be immediately reflected on every other user's tree.

For the auto-update, it was not allowed _refreshing_ nor _polling_, so I created two alternable versions: **SSE** (Server Sent Events) or **WebSockets** (just use the toggle button on the interface)

## Disclaimer

This project was built late at night after full work days or with a two-year-old hanging in my neck, so I couldn't finish some TODO's and had to rush with some implementations as well.

I hope to make some improvements to the project, like unity/integration tests, a micro template system (done), error reporting via email and some fancy buttons/functions.

### Languages, tools and standards

 * PHPOO
 * Javascript
 * MySQL*
 * SSE
 * WebSocket
 * Ratchet
 * MVC
 * AJAX
 * HTML
 * CSS
 * jQuery
 * ORM (custom)
 * Font Awesome
 * Composer
 * Error Handling
 * Templating system (custom)


\* _I thought couchDB would be more suited for the job - as it could store the JSON data just like the tree - but MySQL was adopted to meet the company's practice_

## Components

In order to elaborate a more complete and finished solution, I opted for a few ready to use open source components.

**Front-end:**

* [jQuery](https://jquery.com/) - Javascript micro framework
* [jstree](https://www.jstree.com/) - Javascript main tree
* [notify.js](https://notifyjs.com) - Message display
* [jquery-modal](https://github.com/kylefox/jquery-modal) - Modal display

**Back-end:**

* [Composer](https://getcomposer.org/) - Dependency Manager for PHP
* [Ratchet](http://socketo.me/) - PHP WebSockets
* [Pawl](https://github.com/ratchetphp/Pawl) - Asynchronous WebSocket client


All the rest were built from scratch.

## License

This project is made available under the [Creative Commons CC0 1.0 Universal](https://creativecommons.org/publicdomain/zero/1.0/).

## Contact

Feel free to visit my [personal page](https://filipe.knoedt.net). There you can find more information about myself and get in touch.