<?php

/**
 * MyClass File Doc Comment
 * php version 8.1
 *
 * @category Tests
 * @package  Tests\Unit\Api
 * @author   Marcos Motta <mrcsmotta1@gmail.com>
 * @license  MIT License
 * @link     https://github.com/mrcsmotta1/sistema-gerenciamento-pastelaria
 */

namespace Tests\Unit\Api;

use App\Http\Controllers\Api\ProductController;
use App\Http\Requests\ProductApiRequest;
use App\Models\Product;
use App\Models\ProductType;
use App\Repositories\ProductRepository;
use App\Rules\Base64File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

/**
 * Class ProductControllerTest
 *
 * This file deals with testing on Product.
 *
 * @phpcs
 * php version 8.1
 *
 * @category Tests
 * @package  Tests\Unit\Api
 * @author   Marcos Motta <mrcsmotta1@gmail.com>
 * @license  MIT License
 * @link     https://github.com/mrcsmotta1/sistema-gerenciamento-pastelaria
 */
class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public $base64Image = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAXQAAAF0BVWAulAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAADfSURBVDiNpdK9LkRBFADg76yfRJRKiSi9ghfwHBoR/RYiiEJBNGoakY3oPIBOo1QrJBokCoUXOJoh17XrzjLJFDNzvjlnciYy039Gb1wQEb2IOIyIl4jYlJnVsyQ8Q5b5NC4+b+DETi2ewKCFjzJTLb5o4YOv8w48icsW3v8W03HBcQvv/YgZAaewjkU8FLw9NHYInsFVQSdYwMbIKhtwCTcl43Oj7JVfn9no8T1Wy3oOr+h3dqmAXZw2qpnFG6Yr2qyPd8yXjWVcY1D1yQpawy0ecYetmuyZKT7L+Ov4AOVwwJdv6ZjEAAAAAElFTkSuQmCC";

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_test_create_product_controller(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test that the 'get_index_product' method returns a list of product.
     *
     * This test verifies that the 'get_index_product' method in the controller correctly
     * retrieves and returns a list of product.
     *
     * @return void
     */
    public function test_get_index_product_must_return_product_endpoint(): void
    {
        $productType = ProductType::factory(1)->createOne();

        Storage::fake('public');

        $products = [
            'name' => 'teste',
            "product_type_id" => $productType->id,
            'price' => 19.99,
            'photo' => $this->base64Image,
        ];

        $responseProductType = $this->postJson('/api/products', $products);

        $productType = json_decode($responseProductType->getContent());
        $productTypeBase64 = $productType[0]->product->photo;

        $responseProducts = $this->getJson("/api/products");

        $responseProducts->assertStatus(200);

        $responseProducts->assertJson(function (AssertableJson $json) use ($products, $productTypeBase64) {
            $json->whereAllType([
                '0.product_type_id' => 'integer',
                '0.name' => 'string',
                '0.price' => 'double',
                '0.photo' => 'string',
            ]);

            $json->whereAll([
                '0.product_type_id' => $products['product_type_id'],
                '0.name' => $products['name'],
                '0.price' => $products['price'],
                '0.photo' => $productTypeBase64,
            ]);
        });
    }

    /**
     * Test the creation of a product with a base64-encoded image.
     *
     * This test verifies if the system is capable of creating a new product
     * with an image provided in base64 format. It should ensure that
     * the image is decoded correctly and associated with the created product.
     *
     * @return void
     */
    public function test_store_product_endpoint(): void
    {
        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $response = $this->postJson('/api/products', [
            'name' => 'teste',
            "product_type_id" => $productType->id,
            'price' => 19.99,
            'photo' => $this->base64Image,
        ]);

        $response->assertStatus(201);

        $content = $response->getContent();
        $data = json_decode($content, true);
        $photo = $data[0]['product']['photo'];
        $filename = basename($photo);
        $filename2 = 'img/' . $filename;

        $this->assertTrue(Storage::disk('public')->exists($filename2));
    }

    /**
     * Test that the 'get_index' method returns the expected product type.
     *
     * This test checks if the 'get_index' method of the system correctly
     * returns the product type as expected. It ensures that the system
     * is properly configured to fetch and return the product type.
     *
     * @return void
     */
    public function test_get_show_single_product_endpoint(): void
    {
        $productType = ProductType::factory(1)->createOne();

        Storage::fake('public');

        $products = [
            'name' => 'teste',
            "product_type_id" => $productType->id,
            'price' => 19.99,
            'photo' => $this->base64Image,
        ];

        $responseProductType = $this->postJson('/api/products', $products);

        $productType = json_decode($responseProductType->getContent());
        $productTypeID = $productType[0]->product->id;

        $responseProducts = $this->getJson("/api/products/{$productTypeID}");

        $responseProducts->assertStatus(200);
        $responseProducts->assertJsonCount(8);

        $responseProducts->assertJson(function (AssertableJson $json) use ($products) {
            $json->whereAllType([
                'id' => 'integer',
                'product_type_id' => 'integer',
                'name' => 'string',
                'price' => 'double',
                'photo' => 'string',
                'created_at' => 'string',
                'updated_at' => 'string',
                'deleted_at' => 'null',
            ]);

            $json->whereAll([
                'name' => $products['name'],
                'product_type_id' => $products['product_type_id'],
                'price' => $products['price'],
            ]);
        });
    }

    /**
     * Test the PUT request to update a product via the API endpoint.
     *
     * @return void
     */
    public function test_put_update_product_endpoint(): void
    {
        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $products = [
            'name' => 'teste',
            "product_type_id" => $productType['id'],
            'price' => "19.99",
            'photo' => $this->base64Image,
        ];

        $responseProductType = $this->postJson('/api/products', $products);

        $content = $responseProductType->json();
        $idProduct = $content[0]['product']['id'];

        $productUpdate = [
            "name" => "Test Update",
            "product_type_id" => $productType['id'],
            "price" => "10.10",
            "photo" => $this->base64Image,
        ];

        $response = $this->putJson("/api/products/{$idProduct}", $productUpdate);

        $contentPut = $response->json();

        $namePhoto = $contentPut['photo'];

        $response->assertStatus(200);
        $response->assertJsonCount(8);

        $response->assertJsonStructure([
            'id',
            'name',
            'product_type_id',
            'price',
            'photo',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $response->assertJson(function (AssertableJson $json) use ($productUpdate, $namePhoto) {
            $json->whereAllType([
                'id' => 'integer',
                'product_type_id' => 'integer',
                'name' => 'string',
                "price" => "string",
                'photo' => 'string',
                'created_at' => 'string',
                'updated_at' => 'string',
                'deleted_at' => 'null',
            ]);

            $json->whereAll([
                'name' => $productUpdate['name'],
                'price' => (string) $productUpdate['price'],
                'photo' => $namePhoto,
            ]);
        });
    }

    /**
     * Test the DELETE request to destroy a product via the API endpoint.
     *
     * @return void
     */
    public function test_delete_destroy_product_endpoint(): void
    {
        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $products = [
            'name' => 'teste',
            "product_type_id" => $productType->id,
            'price' => "19.99",
            'photo' => $this->base64Image,
        ];

        $responseProductType = $this->postJson('/api/products', $products);

        $content = $responseProductType->json();
        $idProduct = $content[0]['product']['id'];

        $response = $this->deleteJson("/api/products/{$idProduct}");

        $response->assertStatus(204);
    }

    /**
     * Test if the id does not exist in the show endpoint should return an error
     * when the requested product does not exist.
     *
     * @return void
     */
    public function test_delete_destroy_product_must_return_error_404_when_product_id_does_not_exist_endpoint(): void
    {
        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $products = [
            'name' => 'teste',
            "product_type_id" => $productType->id,
            'price' => "19.99",
            'photo' => $this->base64Image,
        ];

        $this->postJson('/api/products', $products);

        $productID = rand(100, 200);

        $response = $this->deleteJson("/api/products/{$productID}");

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Product not found.',
        ]);
    }

    /**
     * Test the POST request to restore a previously deleted product via the API endpoint.
     *
     * @return void
     */
    public function test_post_restore_product_endpoint(): void
    {
        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $products = [
            'name' => 'teste',
            "product_type_id" => $productType->id,
            'price' => "19.99",
            'photo' => $this->base64Image,
        ];

        $responseProductType = $this->postJson('/api/products', $products);

        $content = $responseProductType->json();
        $idProduct = $content[0]['product']['id'];

        $response = $this->deleteJson("/api/products/{$idProduct}");

        $response = $this->postJson("/api/products/{$idProduct}/restore");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Product restored successfully',
        ]);
    }

    /**
     * Test the POST request to restore a previously deleted product via the API endpoint.
     *
     * @return void
     */
    public function test_post_restore_must_return_404_when_product_type_not_found_in_product_endpoint(): void
    {
        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $products = [
            'name' => 'teste',
            "product_type_id" => $productType->id,
            'price' => "19.99",
            'photo' => $this->base64Image,
        ];

        $responseProductType = $this->postJson('/api/products', $products);

        $content = $responseProductType->json();
        $idProduct = $content[0]['product']['id'];

        $response = $this->deleteJson("/api/products/{$idProduct}");

        $idProduct = rand(100, 200);

        $response = $this->postJson("/api/products/{$idProduct}/restore");

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Product not found.',
        ]);
    }

    /**
     * Test that creating a product with a base64 image is valid.
     *
     * @return void
     */
    public function test_create_product_must_be_valid_with_base64_image_endpoint(): void
    {

        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $base64Image = "data:image/png;base64,iVBORw0KGgoAAAANSUhYXBlLm9yZ5vuPBoAAADfSURBVDiNpdK9LkRBFA";

        $response = $this->postJson('/api/products', [
            'name' => 'teste',
            "product_type_id" => $productType->id,
            'price' => 19.99,
            'photo' => $base64Image,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'O campo photo não possui um arquivo válido existente.',
        ]);
    }

    /**
     * Test that the 'product_type_id' field must exist when creating a product via the API endpoint.
     *
     * @return void
     */
    public function test_product_type_id_field_must_be_exist_when_creating_product_endpoint(): void
    {

        Storage::fake('public');

        $response = $this->postJson('/api/products', [
            'name' => 'teste',
            "product_type_id" => 10,
            'price' => 19.99,
            'photo' => $this->base64Image,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'O campo product type id não é um tipo de produto válido.',
        ]);
    }

    /**
     * Test that the 'name' field must exist when creating a product via the API endpoint.
     *
     * @return void
     */
    public function test_name_field_must_be_required_creating_product_endpoint(): void
    {

        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $response = $this->postJson('/api/products', [
            'name' => '',
            "product_type_id" => $productType->id,
            'price' => 19.99,
            'photo' => $this->base64Image,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'O campo name é obrigatório.',
        ]);
    }

    /**
     * Test that the 'price' field must exist when creating a product via the API endpoint.
     *
     * @return void
     */
    public function test_price_field_must_be_required_creating_product_endpoint(): void
    {

        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $response = $this->postJson('/api/products', [
            'name' => 'teste',
            "product_type_id" => $productType->id,
            'price' => '',
            'photo' => $this->base64Image,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'O campo price é obrigatório.',
        ]);
    }

    /**
     * Test that the 'price' field must be a have value freater than zero when creating a product via the API endpoint.
     *
     * @return void
     */
    public function test_price_field_must_be_have_value_greater_than_zero_when_creating_product_endpoint(): void
    {

        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $response = $this->postJson('/api/products', [
            'name' => 'Teste',
            "product_type_id" => $productType->id,
            'price' => 0,
            'photo' => $this->base64Image,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'O campo price deve ser maior que 0.00. (and 1 more error)',
        ]);
    }

    /**
     * This test should return error 500 in the Product index.
     *
     * @return void
     */
    public function test_index_must_return_error_500_when_there_is_an_error_product_type_endpoint(): void
    {
        $mockRepository = $this->createMock(ProductRepository::class);
        $mockBase64File = $this->createMock(Base64File::class);
        $mockRepository->method('index')->willThrowException(new \Exception('Test exception'));

        $controller = new ProductController($mockRepository, $mockBase64File);
        $response = $controller->index();

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $expectedJson = '{"message": "Ocorreu um erro ao processar a solicitação."}';
        $this->assertJsonStringEqualsJsonString($expectedJson, $response->getContent());
    }

    /**
     * This test should return error 500 in the Product store.
     *
     * @return void
     */
    public function test_store_must_return_error_500_when_there_is_an_error_Product_endpoint(): void
    {
        $mockRepository = $this->createMock(ProductRepository::class);
        $mockBase64File = $this->createMock(Base64File::class);
        $mockRepository->method('add')->willThrowException(new \Exception('Test exception'));

        $controller = new ProductController($mockRepository, $mockBase64File);

        $request = new ProductApiRequest();

        $response = $controller->store($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message": "Ocorreu um erro ao processar a solicitação."}', $response->getContent());
    }

    /**
     * Test if the id does not exist in the show endpoint should return an error
     * when the requested product does not exist.
     *
     * @return void
     */
    public function test_get_show_product_type_must_return_error_404_when_product_id_does_not_exist_endpoint(): void
    {
        $response = $this->getJson('/api/products/2');

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Product not found.',
        ]);
    }

    /**
     * This test should return error 500 in the Product show.
     *
     * @return void
     */
    public function test_show_must_return_error_500_when_there_is_an_error_Product_endpoint(): void
    {
        $mockRepository = $this->createMock(ProductRepository::class);
        $mockBase64File = $this->createMock(Base64File::class);
        $mockRepository->method('find')->willThrowException(new \Exception('Test exception'));

        $controller = new ProductController($mockRepository, $mockBase64File);

        $response = $controller->show('Product_id');

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message": "Ocorreu um erro ao processar a solicitação."}', $response->getContent());
    }

    /**
     * Test if the id does not exist in the show endpoint should return an error
     * when the requested product does not exist.
     *
     * @return void
     */
    public function test_put_update_product_must_return_error_404_when_product_id_does_not_exist_endpoint(): void
    {
        Storage::fake('public');

        $productType = ProductType::factory(1)->createOne();

        $products = [
            'name' => 'teste',
            "product_type_id" => $productType['id'],
            'price' => "19.99",
            'photo' => $this->base64Image,
        ];

        $responseProductType = $this->postJson('/api/products', $products);

        $productUpdate = [
            "name" => "Test Update",
            "product_type_id" => $productType['id'],
            "price" => "10.10",
            "photo" => $this->base64Image,
        ];

        $producID = rand(100, 200);

        $response = $this->putJson("/api/products/{$producID}", $productUpdate);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Product not found.',
        ]);
    }

    /**
     * This test should return error 500 in the Product update.
     *
     * @return void
     */
    public function test_update_must_return_error_500_when_there_is_an_error_product_endpoint(): void
    {
        $mockRepository = $this->createMock(ProductRepository::class);
        $mockRepository->method('find')->willThrowException(new \Exception('Test exception'));
        $mockBase64File = $this->createMock(Base64File::class);
        $mockModel = $this->createMock(Product::class);
        $mockProductApiRequest = $this->createMock(ProductApiRequest::class);

        $controller = new ProductController($mockRepository, $mockBase64File);

        $response = $controller->update($mockProductApiRequest, $mockModel);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message": "Ocorreu um erro ao processar a solicitação."}', $response->getContent());
    }

    /**
     * This test should return error 500 in the Product delete.
     *
     * @return void
     */
    public function test_delete_must_return_error_500_when_there_is_an_error_product_endpoint(): void
    {
        $mockRepository = $this->createMock(ProductRepository::class);
        $mockRepository->method('find')->willThrowException(new \Exception('Test exception'));
        $mockBase64File = $this->createMock(Base64File::class);

        $controller = new ProductController($mockRepository, $mockBase64File);

        $response = $controller->destroy('customer_id');

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message": "Ocorreu um erro ao processar a solicitação."}', $response->getContent());
    }

    /**
     * This test should return error 500 in the Product restore.
     *
     * @return void
     */
    public function test_post_restore_must_return_error_500_when_there_is_an_error_product_endpoint(): void
    {
        $mockRepository = $this->createMock(ProductRepository::class);
        $mockBase64File = $this->createMock(Base64File::class);
        $mockRepository->method('findOnlyTrashed')->willThrowException(new \Exception('Test exception'));

        $controller = new ProductController($mockRepository, $mockBase64File);

        $response = $controller->restore('customer_id');

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message": "Ocorreu um erro ao processar a solicitação."}', $response->getContent());
    }

}
