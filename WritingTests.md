# Writing Unit Tests
to generate a unit test file
```
# this will generate
# /projectDir/tests/unit/AppBundle/Helper/ArrayHelperTest.php
php vendor/bin/codecept generate:test unit AppBundle/Helper/ArrayHelper
```

# Writing Acceptance Tests
To generate an acceptence test "Cest" file
```
php vendor/bin/codecept generate:cest acceptance Login 
```

to generate an empty PageObject to represent the Loginpage
```
# this will generate
# /projectDir/tests/_support/Page/LoginPage.php
php vendor/bin/codecept generate:pageobject LoginPage
```

# Generating Test Data
to autogenerate database records (including records for associated entities) use the DataFactory.
See tests/integration/DataFactoryTest.php for more examples on how to generate db data based on existing entities
```
# generate a member db record with random data and return the created entity 
$member = $this->tester->have(\DbBundle\Entity\Member::class);

# verify the created data
$this->tester->seeInDatabase('customer', ['customer_id' => $member->getId()]);


```
