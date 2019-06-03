#Campaign Payment Manager

## Without Docker

### 1. Clone the repository
```
$ git clone https://github.com/WebDollar/campaign-payment-manager.git
$ cd campaign-payment-manager
```
### 2. Install dependencies
```
$ composer install
$ yarn run build
```

### 3. Run local server
```
$ ./bin/console server:run
```

## With Docker
### 1. Clone the repository
```
$ git clone https://github.com/WebDollar/campaign-payment-manager.git
$ cd campaign-payment-manager
```

### 2. Build Docker
```
$ docker build --build-arg APP_ENV=prod -t campaign-payment-manager .
``` 

### 3. Run image
```
$ docker run --name campaign-payment-manager --rm -p 8000:8000 -e WEBDOLLAR_CLIENT_NODE_1_URL=http://127.0.0.1:3000 -e WEBDOLLAR_CLIENT_NODE_1_USER=user -e WEBDOLLAR_CLIENT_NODE_1_PASS=pas -e APP_ENV=prod campaign-payment-manager
```

