# CHANGELOG

## 2.6.1

- Remove redundant log message

## 2.6.0

- Add config option to log curl requests
- Add default config setter
- Add default logger setter

## 2.5.0

 - Added exception helper methods

## 2.4.2

- Update dependency rexlabs/array-object-php to 2.0.2 (upstream bug)

## 2.4.1

- Fix bug where headers merge instead of overwrite (add test)

## 2.4.0

- Fix bug where base_uri from subclass was ignored
- Look for base_uri in subclass or guzzle config or set manually in client

## 2.3.0

- Add clear instances function to reset static data during testing

## 2.2.0

- Storing separate instances for each subclass of Hyper for static use
    - Static use of `MyHyperSubclass` will return the correct instance created by `MyHyperSubclass`
    - Static use of `Hyper` will return the correct instance created by `Hyper`
- Override `protected static function makeClient` to customise client class (eg replace `new Client` with `new MyClient`)
- Override `protected static function makeConfig` to customise default client config
- Override `protected static function makeGuzzleConfig` to customise default guzzle client
- Override `protected static function getBaseUri` to provide a default base_uri to the client

## 2.1.0

- Add helpers methods to get curl
