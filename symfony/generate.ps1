docker run --rm `
  -v "${PWD}:/local" `
  openapitools/openapi-generator-cli `
  generate `
  -i /local/openapi.yaml `
  -g php-symfony `
  -o /local/src/OpenApi `
  --additional-properties "invokerPackage=App\OpenApi,apiPackage=Api,modelPackage=Model,useAttributes=true"
