# Unit Tests
* small independent tests
* test per class method
* uses phpunit
* fastest among all tests

# Integration Tests
* is like unit test but tests external dependencies like a real working database, cache, email, and other parts of your infrastructure
* also uses phpunit
* kinda slow due to connections with real components

# WebApi Tests
* tests our REST API endpoints
* uses codeception's REST Module
* uses phpbrowser to emulate the HTTP requests/responses
* a little bit slower than unit tests but a lot faster than acceptance tests

# Acceptance Tests
* test an entire page, from login to simulated clicks/form interactions
* requires a fully functional web application and a real web browser
* the slowest tests among the suite

