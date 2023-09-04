<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Alvin Christianto',
            'username' => 'alvinch95',
            'email' => 'christianto.alvin@yahoo.com',
            'password' => bcrypt('123456')
        ]);

        // User::create([
        //     'name' => 'Stevani Chen',
        //     'email' => 'stevani.chen@yahoo.com',
        //     'password' => bcrypt('123456')
        // ]);

        User::factory(3)->create();

        Category::create([
            'name' => 'Single',
            'slug' => 'single'
        ]);

        Category::create([
            'name' => 'Couple',
            'slug' => 'couple'
        ]);

        Category::create([
            'name' => 'Family',
            'slug' => 'family'
        ]);

        Product::factory(20)->create();

        // Product::create([
        //     'title' => 'Virgo',
        //     'slug' => 'virgo',
        //     'excerpt' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit. Sit ab magni quaerat architecto distinctio optio iure quisquam quod! Fuga, aperiam.',
        //     'body' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Laudantium, dolore molestiae voluptate tempore minima harum dolor cum delectus possimus fugiat eveniet, sint sapiente non consequuntur deserunt tenetur recusandae sit autem totam velit officia excepturi. Quia laborum eos ea unde sit optio consequatur nihil molestias at odit vel, veniam repellendus sunt dolor dolorum alias illum pariatur cum asperiores exercitationem ut possimus. Laudantium repellat illum dignissimos consequuntur perspiciatis assumenda tempore laborum obcaecati, possimus ad, excepturi necessitatibus natus incidunt praesentium rem magni odio numquam nihil consequatur? Eum alias aut explicabo quis possimus harum, voluptates esse numquam rerum delectus hic iste earum quod mollitia amet necessitatibus facilis cupiditate. Est voluptas quos praesentium quae sed explicabo quo ipsum vel impedit, quas eligendi debitis, ea cum consectetur iusto molestiae excepturi? Quam distinctio aliquam nulla nobis vel eaque, odio natus voluptatum quas. Atque repudiandae nemo rerum quasi autem, doloribus pariatur alias? Doloribus modi quam accusamus eaque quod magni enim quo est asperiores voluptatem ab explicabo quidem vel blanditiis, rem ea minima corporis aliquam repellendus aspernatur veniam vero? Minus eaque rem odio, commodi quaerat amet nisi, natus eum eius tempora enim similique officia atque nesciunt. Harum velit autem quam fugiat nobis at quia blanditiis hic in, minus ipsa!',
        //     'price' => 115000,
        //     'category_id' => 1,
        //     'user_id' => 1
        // ]);

        // Product::create([
        //     'title' => 'Cancer',
        //     'slug' => 'cancer',
        //     'excerpt' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit. Sit ab magni quaerat architecto distinctio optio iure quisquam quod! Fuga, aperiam.',
        //     'body' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Laudantium, dolore molestiae voluptate tempore minima harum dolor cum delectus possimus fugiat eveniet, sint sapiente non consequuntur deserunt tenetur recusandae sit autem totam velit officia excepturi. Quia laborum eos ea unde sit optio consequatur nihil molestias at odit vel, veniam repellendus sunt dolor dolorum alias illum pariatur cum asperiores exercitationem ut possimus. Laudantium repellat illum dignissimos consequuntur perspiciatis assumenda tempore laborum obcaecati, possimus ad, excepturi necessitatibus natus incidunt praesentium rem magni odio numquam nihil consequatur? Eum alias aut explicabo quis possimus harum, voluptates esse numquam rerum delectus hic iste earum quod mollitia amet necessitatibus facilis cupiditate. Est voluptas quos praesentium quae sed explicabo quo ipsum vel impedit, quas eligendi debitis, ea cum consectetur iusto molestiae excepturi? Quam distinctio aliquam nulla nobis vel eaque, odio natus voluptatum quas. Atque repudiandae nemo rerum quasi autem, doloribus pariatur alias? Doloribus modi quam accusamus eaque quod magni enim quo est asperiores voluptatem ab explicabo quidem vel blanditiis, rem ea minima corporis aliquam repellendus aspernatur veniam vero? Minus eaque rem odio, commodi quaerat amet nisi, natus eum eius tempora enim similique officia atque nesciunt. Harum velit autem quam fugiat nobis at quia blanditiis hic in, minus ipsa!',
        //     'price' => 120000,
        //     'category_id' => 1,
        //     'user_id' => 2
        // ]);

        // Product::create([
        //     'title' => 'Idrus',
        //     'slug' => 'idrus',
        //     'excerpt' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit. Sit ab magni quaerat architecto distinctio optio iure quisquam quod! Fuga, aperiam.',
        //     'body' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Laudantium, dolore molestiae voluptate tempore minima harum dolor cum delectus possimus fugiat eveniet, sint sapiente non consequuntur deserunt tenetur recusandae sit autem totam velit officia excepturi. Quia laborum eos ea unde sit optio consequatur nihil molestias at odit vel, veniam repellendus sunt dolor dolorum alias illum pariatur cum asperiores exercitationem ut possimus. Laudantium repellat illum dignissimos consequuntur perspiciatis assumenda tempore laborum obcaecati, possimus ad, excepturi necessitatibus natus incidunt praesentium rem magni odio numquam nihil consequatur? Eum alias aut explicabo quis possimus harum, voluptates esse numquam rerum delectus hic iste earum quod mollitia amet necessitatibus facilis cupiditate. Est voluptas quos praesentium quae sed explicabo quo ipsum vel impedit, quas eligendi debitis, ea cum consectetur iusto molestiae excepturi? Quam distinctio aliquam nulla nobis vel eaque, odio natus voluptatum quas. Atque repudiandae nemo rerum quasi autem, doloribus pariatur alias? Doloribus modi quam accusamus eaque quod magni enim quo est asperiores voluptatem ab explicabo quidem vel blanditiis, rem ea minima corporis aliquam repellendus aspernatur veniam vero? Minus eaque rem odio, commodi quaerat amet nisi, natus eum eius tempora enim similique officia atque nesciunt. Harum velit autem quam fugiat nobis at quia blanditiis hic in, minus ipsa!',
        //     'price' => 230000,
        //     'category_id' => 3,
        //     'user_id' => 1
        // ]);

        // Product::create([
        //     'title' => 'Aries',
        //     'slug' => 'aries',
        //     'excerpt' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit. Sit ab magni quaerat architecto distinctio optio iure quisquam quod! Fuga, aperiam.',
        //     'body' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Laudantium, dolore molestiae voluptate tempore minima harum dolor cum delectus possimus fugiat eveniet, sint sapiente non consequuntur deserunt tenetur recusandae sit autem totam velit officia excepturi. Quia laborum eos ea unde sit optio consequatur nihil molestias at odit vel, veniam repellendus sunt dolor dolorum alias illum pariatur cum asperiores exercitationem ut possimus. Laudantium repellat illum dignissimos consequuntur perspiciatis assumenda tempore laborum obcaecati, possimus ad, excepturi necessitatibus natus incidunt praesentium rem magni odio numquam nihil consequatur? Eum alias aut explicabo quis possimus harum, voluptates esse numquam rerum delectus hic iste earum quod mollitia amet necessitatibus facilis cupiditate. Est voluptas quos praesentium quae sed explicabo quo ipsum vel impedit, quas eligendi debitis, ea cum consectetur iusto molestiae excepturi? Quam distinctio aliquam nulla nobis vel eaque, odio natus voluptatum quas. Atque repudiandae nemo rerum quasi autem, doloribus pariatur alias? Doloribus modi quam accusamus eaque quod magni enim quo est asperiores voluptatem ab explicabo quidem vel blanditiis, rem ea minima corporis aliquam repellendus aspernatur veniam vero? Minus eaque rem odio, commodi quaerat amet nisi, natus eum eius tempora enim similique officia atque nesciunt. Harum velit autem quam fugiat nobis at quia blanditiis hic in, minus ipsa!',
        //     'price' => 180000,
        //     'category_id' => 2,
        //     'user_id' => 2
        // ]);

    }
}
