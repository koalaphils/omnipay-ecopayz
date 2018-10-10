# How to run unit tests
running unit test
```
vendor/bin/codecept run unit
```
running unit test with a simple text coverage report
```
# you need to have xdebug installed for this to work
# check if you have xdebug installed using 
php -i | grep xdebug
# run tests and generate simple report
vendor/bin/codecept run unit --coverage-text
```

# How to run Acceptance tests

Install and run chromedriver 
```
# install chromedriver
# ref: https://tecadmin.net/setup-selenium-chromedriver-on-ubuntu/
wget https://chromedriver.storage.googleapis.com/2.35/chromedriver_linux64.zip
unzip chromedriver_linux64.zip
sudo mv chromedriver /usr/bin/chromedriver
sudo chown root:root /usr/bin/chromedriver
sudo chmod +x /usr/bin/chromedriver

# start chromedriver
chromedriver --url-base=/wd/hub
```
Run the acceptance suite and generate HTML reports
```
# run the acceptance tests with html reports
php vendor/bin/codecept run acceptance --html
```
View the reports
```
# move the report html to web accessible path
# access the output at http://<your-local-ip>/codeception/report.html
ln -s /path/to/ac66bo/tests/_output/ /path/to/ac66bo/web/codeception
```````````