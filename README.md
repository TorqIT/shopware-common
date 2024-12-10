
# shopware-torq-common
This repository (plugin) provides a centralized location for commonly reused code across multiple Torq Shopware projects, ensuring a single point of truth for shared functionality and enabling easier maintenance.

---

## Submodule Initialization
After cloning the **shopware-skeleton** Git repository, pull down the submodule code by executing the following commands in the root directory of **shopware-skeleton**.

```bash
git submodule init
git submodule update
```

> **Note**: This ensures the submodule is initialized and its content is fetched.

---

## Submodule Installation
To install the submodule and set up the container, follow these steps:

1. **Build and start the container**:
   ```bash
   docker compose up --build
   ```

2. **Access the container**:
   Connect to the container as the **www-data** user by running:
   ```bash
   docker compose exec -it -u www-data php bash
   ```

3. **Install the `torqcommon` plugin**:
   Inside the container, add the **torqcommon** plugin to your Shopware project using Composer:
   ```bash
   composer require torq/torqcommon
   ```

> **Note**: The `composer require` command will add the **torqcommon** plugin as a dependency and update the `composer.json` and `composer.lock` files accordingly.  It will also add the necessary files and folders to the **vendor** directory.
