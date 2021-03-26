#### System Requirements

NPM v6.4.1
Node v8.12.0

### Building the image

```
$ cd .docker/
$ docker build -t wallet-api/latest -f DockerfileBlockchainWallet .
```

### Running the container
```
$ docker run -p 5050:3000 -d wallet-api/latest
```