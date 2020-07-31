#!/bin/bash

rm -rf dist/
mkdir dist
cp -r css/ dist/
cp -r includes/ dist/
cp -r templates/ dist/
cp youtube-search.php dist/
cp release.txt dist/
cp composer.json dist/
npm run build
cd dist
mkdir js
cd ../
cp build/index.js dist/js
cp youtube-search.defines.dist.php dist/youtube-search.defines.php
