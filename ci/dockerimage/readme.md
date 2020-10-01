+ Docker images are build from cache to archive reproducibility.
+ In case all layers are pulled from cache, we use the cache image. This way the image sha256 dose not change.
+ There are "cache-invalidator" images. They are build with no cache and produce a single file. The file should change
  if and only if the cache needs to be invalidated. E.g. if apk add nginx installs a newer version of the nginx packet,
  then the file should change.
+ "provider" images are a basically the same as a cache-invalidator, but they provide multiple files. E.g. a provider
  provides the icon-set. The icon-set is a git submodule. We cannot copy the .git folder into the main image. The 
  provider initialises the submodule and the main image copies the icon-set folder.
+ The Dockerfile is split into multiple files. Each file contains the image code and the code for its invalidators and
  providers. The files need to be separated. Otherwise, building single targets would build all stages before.
+ The images depend on each other.
  ```
    ┌───────────────────────────────────┐
    │                                   ╽ 
  base ───► source ───► build ─copy─► built
    │          │                        │
    │          ├────────copy────────┐   │ 
    ▾          ▾                    ▾   ▾
   dev    test-source            test-built
  ```