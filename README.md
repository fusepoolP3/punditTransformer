# Symfony2 Docker Image

This image expects a volume to be mounted on run and will perform the necessary commands to get the Symfony2 application into a production ready environment:

  - Composer Install (With optimised autoloader)
  - Assets Install
  - Assetic Dump
  - Cache Clear

