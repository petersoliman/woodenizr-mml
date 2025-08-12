## Commands
Install npm packages.

```sh
npm install
```
#### Local development
----
1. Start Webpack to starting compiling files.
```sh
npm run start:assets
```
2. Start Live server on default browser.
```sh 
npm run start:server
```
3. Start Database server on default browser.
```sh 
npm run start:db
```
4. Start Dev server
```sh 
npm run start
```



#### Prepare for production
---
1. Compile and minify all files using Webpack.
```sh
npm run build:assets
```
2. Generate Critical CSS and inject it to generated files.
```sh
npm run build:critical
```

Build Production Assets and critical css
```sh
npm run build
```
