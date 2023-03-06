<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\User;
use App\Services\ShopifyWebService;
use App\Services\StickyService;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientInitialBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 240;

    public $productLimit = 10;

    protected $user;
    protected $shopify;
    protected $sticky;
    protected $cursor;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $cursor=null)
    {
        $this->user = $user;
        $this->cursor = ($cursor) ? $cursor : 1;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (env('APP_ENV') != 'testing'){
            if ($this->batch()->cancelled()) {
                // Determine if the batch has been cancelled...
                return;
            }
        }

        $this->shopify = new ShopifyWebService($this->user->client);
        $this->sticky = new StickyService($this->user->client);

//        Log::debug("ClientInitialBatch client: {$this->user->client->id}  , page: {$this->cursor}");

        // Import a portion of the product from Shopify...
        $cursor = $this->syncProducts($this->cursor);

        if ($cursor){
            $this->cursor++;
            // check if it not testing job & we have another page - add next page processing to batch
            if (env('APP_ENV') != 'testing'){
                $this->batch()->add(new ClientInitialBatch($this->user, $this->cursor));
            }
        } else {

            // looks we finished with products
            // proceed Sticky final steps
//            Log::debug("ClientInitialBatch client: {$this->user->client->id}  , start StickyInitialBatch else");
            $this->batch()->add(new StickyInitialBatch($this->user));
        }


    }

    private function syncProducts()
    {
        $cursor = ($this->cursor) ? $this->cursor : 1;

//        Log::debug('syncProducts');

        $prodyctsResource = $this->shopify->getProducts(['limit' => $this->productLimit, 'page' => $cursor]);

        //
//        Log::debug("prodyctsResource:");
//        Log::debug($prodyctsResource);

        foreach ($prodyctsResource as $item) {

//            Log::debug("foreach prodyctsResource");
//            Log::debug(print_r($item,1));

            //TODO: insert product to DB
            $images = collect($item['images']);
            $options = collect($item['options']);
            $variants = collect($item['variants']);

            if ($images->count()) {
                $imageSrc = ($variants->where('position', 1)->first() && isset($variants->where('position',
                            1)->first()['featured_image']))
                    ? $variants->where('position', 1)->first()['featured_image']['src']
                    : $images->first()['src'];

                $imageId = ($variants->where('position', 1)->first() && isset($variants->where('position',
                            1)->first()['featured_image']))
                    ? $variants->where('position', 1)->first()['featured_image']['id']
                    : $images->first()['id'];

            } else {
                $imageId = null;
                $imageSrc = null;
            }


            $product = Product::create([

                'name' => $item['title'],

                // get prices from variat at position 1
                'price' => ($variants->where('position', 1)->first()) ? $variants->where('position',
                    1)->first()['price'] : 0,
                'compare_at_price' => ($variants->where('position', 1)->first() && $variants->where('position',
                        1)->first()['compare_at_price']) ? $variants->where('position',
                    1)->first()['compare_at_price'] : 0,

                'featured_image' => ($imageSrc) ? $this->getImageFromUrl($imageSrc, $imageId) : null,

                'shopify_product_id' => $item['id'],
                'client_id' => $this->user->client->id,
                'sticky_id' => 0,

                'created_at' => Carbon::parse($item['created_at']),
                'updated_at' => Carbon::parse($item['updated_at']),

                //Product options names
                'option1' => ($options->where('position', 1)->first()) ? $options->where('position',
                    1)->first()['name'] : null,
                'option2' => ($options->where('position', 2)->first()) ? $options->where('position',
                    2)->first()['name'] : null,
                'option3' => ($options->where('position', 3)->first()) ? $options->where('position',
                    3)->first()['name'] : null,

                'weight' => ($variants->where('position', 1)->first() && isset($variants->where('position',
                            1)->first()['grams'])) ? $variants->where('position', 1)->first()['grams'] : null,
                'weight_unit' => ($variants->where('position', 1)->first() && isset($variants->where('position',
                            1)->first()['grams'])) ? 'g' : null,

                'sku' => ($variants->where('position', 1)->first()) ? $variants->where('position', 1)->first()['sku'] : null,

            ]);

            // adding product variants
            foreach ($item['variants'] as $variant) {


                if ($images->count()) {
                    $imageSrc = (isset($variant['featured_image']))
                        ? $variant['featured_image']['src']
                        : $images->first()['src'];

                    $imageId = (isset($variant['featured_image']))
                        ? $variant['featured_image']['id']
                        : $images->first()['id'];

                } else {
                    $imageId = null;
                    $imageSrc = null;
                }


                $product->variants()->create([
                    'name' => $variant['title'],
                    'price' => $variant['price'],
                    'compare_at_price' => ($variant['compare_at_price']) ? $variant['compare_at_price'] : 0,

                    'image' => ($imageSrc) ? $this->getImageFromUrl($imageSrc, $imageId) : $product->featured_image,

                    'option1' => $variant['option1'],
                    'option2' => $variant['option2'],
                    'option3' => $variant['option3'],

                    'weight' => (isset($variant['grams'])) ? $variant['grams'] : null,
                    'weight_unit' => (isset($variant['grams'])) ? 'g' : null,

                    'shopify_variant_id' => $variant['id'],

                    'sticky_id' => 0,

                    'created_at' => Carbon::parse($variant['created_at']),
                    'updated_at' => Carbon::parse($variant['updated_at']),

                    'sku' => $variant['sku'] ?? null,
                ]);

            }


            // Export products to Sticky
            $this->exportStickyProduct($product);

        }

        if (count($prodyctsResource) == $this->productLimit) {
            return $cursor++;
        }

        return null;
    }


    private function exportStickyProduct(Product $product)
    {
        $variants = $product->variants;

        $attributes = [];
        for ($i = 1; $i < 3; $i++) {
            if ($product["option{$i}"]) {
                $attributes[] = [
                    'name' => $product["option{$i}"],
                    'options' => $variants->pluck("option{$i}")->unique()->toArray()
                ];
            }
        }

        // check if product have sticky_id
        if (!$product->sticky_id) {
            $stickyProduct = $this->sticky->createProduct([
                'name' => $product->name,
                'sku' => (strlen($product->sku)) ? $product->sku : Str::slug($product->name)."_".$this->sticky->uniqidReal(),
                'category_id' => $this->user->client->sticky_category_id,
                'price' => $product->price,
                'cost_of_goods' => 0,
                'restocking_fee' => 0,
                'max_quantity' => 100,
                'auto_create_variants' => true,
                'attributes' => $attributes
            ]);

//                    Log::debug(json_encode($stickyProduct));

            // Update Product with Sticky product ID
            $product->update([
                'sticky_id' => $stickyProduct['data']['id']
            ]);
        }

    }


    private function getImageFromUrl($url = null, $image_id = null)
    {

        try {

            // store image in Storage
            if (strpos($url, 'http') === false) {
                $url = "http:".$url;
            }
            $url = preg_replace('/\?.*/', '', $url);
            $info = pathinfo($url);
            $image = file_get_contents($url);
            $path = "products/{$image_id}.{$info['extension']}";

            if ($image !== false) {
                if (!Storage::exists($path)) {
                    Storage::put($path, $image,);
                }
                return Storage::url($path);
            }
            return null;

        } catch (\Exception $e){

            Log::debug("Error getImageFromUrl {$image_id} : {$url}");
            return null;

        }


    }
}
