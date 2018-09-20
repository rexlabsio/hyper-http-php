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