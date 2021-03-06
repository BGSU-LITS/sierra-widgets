# sierra-widgets
Add widgets to bib records in the Sierra WebPAC.

## Javascript
The PHP application generates a Javascript file based on the configuration described below. This file is available in the public directory at `/js/index.js`. For example, if the application is installed on a path named `widgets` on the server `example.edu`, the Javascript may be referenced with:
```
<script src="https://example.edu/widgets/js/index.js"></script>
```

At BGSU, this Javascript is added to the `screens/bib_display.html` file for the Sierra WebPAC.

### BGSU's Javascript as Example
You may want to use your own application to handle applications instead of the version offered by this project. In that case, you may be interested in reviewing BGSU's copy of the Javascript file for adaption to your own system. It is online at:
https://lib.bgsu.edu/catalog/widgets/js/index.js

## PHP Application
### Installation
The package can either be installed by cloning the repository or downloading and extracting an archive from the [releases](https://github.com/BGSU-LITS/sierra-widgets/releases) page. The resulting directory should be placed outside of the document root for the web server.

#### Dependencies
Dependencies are included with the archives provided by the [releases](https://github.com/BGSU-LITS/sierra-widgets/releases) page. When installed by cloning the repository, dependencies must be installed via [Composer](https://getcomposer.org/) by running a command like the following from the application's directory:
```
php composer.phar install
```

#### Permissions
Write permissions must be granted to the webserver for the `cache/` directory. If you configure the application to store a log file below, that directory must also be writable by the webserver.

### Configuration
Configuration is stored in the `config.yaml` file. An example based on what BGSU uses is provided in the `configy.yaml.example` file, and you can begin by copying it over:
```
cp config.yaml.example config.yaml
```

The file uses [YAML](http://yaml.org/) to store configuration values.

#### Application Settings
The setting `app.debug` is primarly enabled when developing the application, and provides additional details with error messages. `app.log` specifies the full path to a log file to store error messages generated by the application.

`app.redirect` should be configured to redirect users to your catalog or another site if they access the application directly instead of via the buttons added to the catalog.

#### Template Settings
These settings are primarly included to enable the use of a centralized collection of templates. If left as the default, you may edit the file `templates/page.html.twig` to configure the standard template used for each page. See below for further details about templates.

#### SMTP Settings
`smtp.host` and `smtp.port` should be configured with the details for your SMTP server.

`smtp.from.name` and `smtp.from.address` should specify the name and email address of the sender of any message from a widget, including SMS.

`smtp.subject` specifies the subject of any message from a widget, including SMS.

`smtp.carriers` is a list of SMS carriers to display as options for sending SMS messages. Each item should have a `name` for the carrier's name, and a `host` with the domain name to use as an email-to-SMS gateway for that carrier. The example file has the most common carriers in the United States already defined.

#### Citations Settings
To use citations, you must configure the `citations.wskey` option with a [WorldCat Search API Web Services Key](https://platform.worldcat.org/wskey/).

### Web Server
The `public/` directory is what needs to be served by your web server. BGSU accomplishes this by creating a soft link to `public/` within the server's document root, for example:
```
cd /path/to/htdocs
ln -s ../path/to/application/public widgets
```

You may also consult the documentation of you server for alternative ways of serving specific directories.
