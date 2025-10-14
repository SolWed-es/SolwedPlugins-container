<?php

namespace FacturaScripts\Plugins\SolwedTiendaWeb\Lib;

/**
 * Handles WooCommerce category operations
 */
class WooCategoryService
{
    private $wooClient;

    public function __construct()
    {
        error_log("WooCategoryService::__construct - Initializing category service");
        try {
            $this->wooClient = WooHelper::getClient();
            error_log("WooCategoryService::__construct - Successfully initialized WooCommerce client");
        } catch (\Exception $e) {
            error_log("WooCategoryService::__construct - Error initializing WooCommerce client: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all WooCommerce categories
     *
     * @param int $perPage Number of categories to retrieve per page
     * @return array|false Array of categories or false on error
     */
    public function getCategories(int $perPage = 100)
    {
        error_log("WooCategoryService::getCategories - Fetching categories with perPage: {$perPage}");

        try {
            $params = [
                'per_page' => $perPage,
                'orderby' => 'name',
                'order' => 'asc',
                'hide_empty' => false
            ];

            $categories = $this->wooClient->get('products/categories', $params);

            if (!$categories) {
                error_log("WooCategoryService::getCategories - No categories returned from API");
                return false;
            }

            $categoryCount = is_array($categories) ? count($categories) : 0;
            error_log("WooCategoryService::getCategories - Successfully fetched {$categoryCount} categories");

            return $categories;
        } catch (\Exception $e) {
            error_log("WooCategoryService::getCategories - Error fetching categories: " . $e->getMessage());
            error_log("WooCategoryService::getCategories - Exception trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Create a new WooCommerce category
     *
     * @param string $name Category name
     * @param string|null $description Category description (optional)
     * @param string|null $slug Category slug (optional, will be auto-generated)
     * @param int $parentId Parent category ID (0 for root category)
     * @return object|false Created category object or false on error
     */
    public function createCategory(string $name, ?string $description = null, ?string $slug = null, int $parentId = 0)
    {
        error_log("WooCategoryService::createCategory - Creating category: {$name} (parent ID: {$parentId})");

        if (empty($name)) {
            error_log("WooCategoryService::createCategory - Error: Category name is empty");
            return false;
        }

        try {
            $categoryData = [
                'name' => trim($name)
            ];

            if (!empty($description)) {
                $categoryData['description'] = trim($description);
            }

            if (!empty($slug)) {
                $categoryData['slug'] = trim($slug);
            }

            // Add parent category if specified
            if ($parentId > 0) {
                $categoryData['parent'] = $parentId;
                error_log("WooCategoryService::createCategory - Setting parent category ID: {$parentId}");
            }

            error_log("WooCategoryService::createCategory - Sending data to WooCommerce API: " . json_encode($categoryData));

            $category = $this->wooClient->post('products/categories', $categoryData);

            if (!$category || !isset($category->id)) {
                error_log("WooCategoryService::createCategory - Error: Invalid response from WooCommerce API");
                return false;
            }

            error_log("WooCategoryService::createCategory - Successfully created category with ID: {$category->id}");

            return $category;
        } catch (\Exception $e) {
            error_log("WooCategoryService::createCategory - Error creating category '{$name}': " . $e->getMessage());
            error_log("WooCategoryService::createCategory - Exception trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Get a specific category by ID
     *
     * @param int $categoryId
     * @return object|false
     */
    public function getCategory(int $categoryId)
    {
        error_log("WooCategoryService::getCategory - Fetching category ID: {$categoryId}");

        try {
            $category = $this->wooClient->get("products/categories/{$categoryId}");

            if (!$category) {
                error_log("WooCategoryService::getCategory - Category {$categoryId} not found");
                return false;
            }

            error_log("WooCategoryService::getCategory - Successfully fetched category: {$category->name}");

            return $category;
        } catch (\Exception $e) {
            error_log("WooCategoryService::getCategory - Error fetching category {$categoryId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a WooCommerce category
     *
     * @param int $categoryId
     * @param array $data
     * @return object|false
     */
    public function updateCategory(int $categoryId, array $data)
    {
        error_log("WooCategoryService::updateCategory - Updating category ID: {$categoryId}");

        try {
            $category = $this->wooClient->put("products/categories/{$categoryId}", $data);

            if (!$category) {
                error_log("WooCategoryService::updateCategory - Error updating category {$categoryId}");
                return false;
            }

            error_log("WooCategoryService::updateCategory - Successfully updated category: {$category->name}");

            return $category;
        } catch (\Exception $e) {
            error_log("WooCategoryService::updateCategory - Error updating category {$categoryId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a WooCommerce category
     *
     * @param int $categoryId
     * @param bool $force Force delete (bypass trash)
     * @return bool
     */
    public function deleteCategory(int $categoryId, bool $force = false): bool
    {
        error_log("WooCategoryService::deleteCategory - Deleting category ID: {$categoryId} (force: " . ($force ? 'yes' : 'no') . ")");

        try {
            $params = $force ? ['force' => true] : [];
            $result = $this->wooClient->delete("products/categories/{$categoryId}", $params);

            if (!$result) {
                error_log("WooCategoryService::deleteCategory - Error deleting category {$categoryId}");
                return false;
            }

            error_log("WooCategoryService::deleteCategory - Successfully deleted category {$categoryId}");

            return true;
        } catch (\Exception $e) {
            error_log("WooCategoryService::deleteCategory - Error deleting category {$categoryId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a category exists by name
     *
     * @param string $name
     * @return int|false Category ID if exists, false if not found
     */
    public function categoryExistsByName(string $name)
    {
        error_log("WooCategoryService::categoryExistsByName - Checking if category exists: {$name}");

        try {
            $params = [
                'search' => trim($name),
                'per_page' => 10
            ];

            $categories = $this->wooClient->get('products/categories', $params);

            if (!$categories || !is_array($categories)) {
                error_log("WooCategoryService::categoryExistsByName - No categories found for search: {$name}");
                return false;
            }

            foreach ($categories as $category) {
                if (strtolower(trim($category->name)) === strtolower(trim($name))) {
                    error_log("WooCategoryService::categoryExistsByName - Found existing category '{$name}' with ID: {$category->id}");
                    return $category->id;
                }
            }

            error_log("WooCategoryService::categoryExistsByName - Category '{$name}' does not exist");
            return false;
        } catch (\Exception $e) {
            error_log("WooCategoryService::categoryExistsByName - Error checking category existence '{$name}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get categories formatted for select dropdown with hierarchy support
     *
     * @return array
     */
    public function getCategoriesForSelect(): array
    {
        error_log("WooCategoryService::getCategoriesForSelect - Getting categories for select dropdown");

        $categories = $this->getCategories();

        if (!$categories) {
            error_log("WooCategoryService::getCategoriesForSelect - No categories available for select");
            return [];
        }

        $selectOptions = [];

        foreach ($categories as $category) {
            $selectOptions[] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug ?? '',
                'parent' => $category->parent ?? 0,
                'count' => $category->count ?? 0,
                'description' => $category->description ?? ''
            ];
        }

        error_log("WooCategoryService::getCategoriesForSelect - Prepared " . count($selectOptions) . " categories for select");

        return $selectOptions;
    }

    /**
     * Validate category data before API call
     *
     * @param array $categoryData
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateCategoryData(array $categoryData): array
    {
        error_log("WooCategoryService::validateCategoryData - Validating category data");

        $errors = [];

        if (empty($categoryData['name']) || trim($categoryData['name']) === '') {
            $errors[] = 'Category name is required';
        } elseif (strlen(trim($categoryData['name'])) < 2) {
            $errors[] = 'Category name must be at least 2 characters long';
        }

        // Check for duplicate names
        if (!empty($categoryData['name'])) {
            $existingId = $this->categoryExistsByName($categoryData['name']);
            if ($existingId) {
                $errors[] = "Category '{$categoryData['name']}' already exists";
            }
        }

        $isValid = empty($errors);

        if ($isValid) {
            error_log("WooCategoryService::validateCategoryData - Category data is valid");
        } else {
            error_log("WooCategoryService::validateCategoryData - Category data validation failed: " . implode(', ', $errors));
        }

        return [
            'valid' => $isValid,
            'errors' => $errors
        ];
    }

    /**
     * Sync all FacturaScripts Familias to WooCommerce categories
     * FacturaScripts is the source of truth
     *
     * @return array ['success' => bool, 'message' => string, 'synced' => int, 'errors' => array]
     */
    public function syncFamiliasToWooCommerce(): array
    {
        error_log("WooCategoryService::syncFamiliasToWooCommerce - Starting category sync from FacturaScripts to WooCommerce");

        try {
            // Load all Familias from FacturaScripts
            $familiaModel = new \FacturaScripts\Dinamic\Model\Familia();
            $allFamilias = $familiaModel->all();

            if (empty($allFamilias)) {
                error_log("WooCategoryService::syncFamiliasToWooCommerce - No familias found in FacturaScripts");
                return [
                    'success' => true,
                    'message' => 'No hay categorías para sincronizar',
                    'synced' => 0,
                    'errors' => []
                ];
            }

            error_log("WooCategoryService::syncFamiliasToWooCommerce - Found " . count($allFamilias) . " familias to sync");

            // Sort familias by hierarchy (parents first)
            $sortedFamilias = $this->sortFamiliasByHierarchy($allFamilias);

            $syncedCount = 0;
            $errors = [];

            foreach ($sortedFamilias as $familia) {
                try {
                    error_log("WooCategoryService::syncFamiliasToWooCommerce - Processing familia: {$familia->descripcion} (codfamilia: {$familia->codfamilia})");

                    // Determine parent WooCommerce category ID
                    $parentWcId = 0;
                    if (!empty($familia->madre)) {
                        error_log("WooCategoryService::syncFamiliasToWooCommerce - Familia has parent (madre): {$familia->madre}");

                        // Load parent familia to get its wc_category_id
                        $parentFamilia = new \FacturaScripts\Dinamic\Model\Familia();
                        if ($parentFamilia->loadFromCode($familia->madre)) {
                            $parentWcId = $parentFamilia->wc_category_id ?? 0;
                            error_log("WooCategoryService::syncFamiliasToWooCommerce - Parent WC category ID: {$parentWcId}");
                        }
                    }

                    // Check if categoria already exists in WooCommerce
                    if (!empty($familia->wc_category_id)) {
                        error_log("WooCategoryService::syncFamiliasToWooCommerce - Familia already has WC category ID: {$familia->wc_category_id}, updating...");

                        // Update existing category
                        $updateData = [
                            'name' => $familia->descripcion,
                            'parent' => $parentWcId
                        ];

                        $wcCategory = $this->updateCategory($familia->wc_category_id, $updateData);

                        if ($wcCategory) {
                            error_log("WooCategoryService::syncFamiliasToWooCommerce - Successfully updated WC category: {$familia->descripcion}");
                            $syncedCount++;
                        } else {
                            $errorMsg = "Error al actualizar categoría '{$familia->descripcion}' en WooCommerce";
                            error_log("WooCategoryService::syncFamiliasToWooCommerce - " . $errorMsg);
                            $errors[] = $errorMsg;
                        }
                    } else {
                        error_log("WooCategoryService::syncFamiliasToWooCommerce - Creating new WC category for: {$familia->descripcion}");

                        // Create new category
                        $wcCategory = $this->createCategory($familia->descripcion, null, null, $parentWcId);

                        if ($wcCategory && isset($wcCategory->id)) {
                            error_log("WooCategoryService::syncFamiliasToWooCommerce - Successfully created WC category with ID: {$wcCategory->id}");

                            // Save WooCommerce category ID to Familia
                            $familia->wc_category_id = $wcCategory->id;
                            if ($familia->save()) {
                                error_log("WooCategoryService::syncFamiliasToWooCommerce - Successfully saved wc_category_id to Familia");
                                $syncedCount++;
                            } else {
                                $errorMsg = "Error al guardar wc_category_id en Familia '{$familia->descripcion}'";
                                error_log("WooCategoryService::syncFamiliasToWooCommerce - " . $errorMsg);
                                $errors[] = $errorMsg;
                            }
                        } else {
                            $errorMsg = "Error al crear categoría '{$familia->descripcion}' en WooCommerce";
                            error_log("WooCategoryService::syncFamiliasToWooCommerce - " . $errorMsg);
                            $errors[] = $errorMsg;
                        }
                    }
                } catch (\Exception $e) {
                    $errorMsg = "Error sincronizando familia '{$familia->descripcion}': " . $e->getMessage();
                    error_log("WooCategoryService::syncFamiliasToWooCommerce - " . $errorMsg);
                    $errors[] = $errorMsg;
                }
            }

            $message = "Sincronizadas {$syncedCount} de " . count($sortedFamilias) . " categorías";
            if (!empty($errors)) {
                $message .= " con " . count($errors) . " errores";
            }

            error_log("WooCategoryService::syncFamiliasToWooCommerce - Sync complete: {$message}");

            return [
                'success' => $syncedCount > 0,
                'message' => $message,
                'synced' => $syncedCount,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            error_log("WooCategoryService::syncFamiliasToWooCommerce - Exception: " . $e->getMessage());
            error_log("WooCategoryService::syncFamiliasToWooCommerce - Exception trace: " . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Error al sincronizar categorías: ' . $e->getMessage(),
                'synced' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Sort familias by hierarchy to ensure parents are processed before children
     *
     * @param array $familias
     * @return array
     */
    private function sortFamiliasByHierarchy(array $familias): array
    {
        error_log("WooCategoryService::sortFamiliasByHierarchy - Sorting " . count($familias) . " familias by hierarchy");

        $sorted = [];
        $processed = [];
        $maxIterations = count($familias) * 2; // Prevent infinite loop
        $iteration = 0;

        while (count($sorted) < count($familias) && $iteration < $maxIterations) {
            $iteration++;

            foreach ($familias as $familia) {
                // Skip if already processed
                if (isset($processed[$familia->codfamilia])) {
                    continue;
                }

                // If no parent or parent already processed, add to sorted list
                if (empty($familia->madre) || isset($processed[$familia->madre])) {
                    $sorted[] = $familia;
                    $processed[$familia->codfamilia] = true;
                }
            }
        }

        error_log("WooCategoryService::sortFamiliasByHierarchy - Sorted " . count($sorted) . " familias in {$iteration} iterations");

        return $sorted;
    }
}
