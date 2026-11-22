<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        $names = [
            'فيزياء للجميع',
            'مجموعة البرمجة العربية',
            'تصوير وفن',
            'تطوير الويب العربي',
            'كتاب ومطالعة',
            'طهي ووصفات',
            'سفر وسياحة',
            'لياقة وصحة',
            'أفلام وسينما',
            'تقنية المستقبل',
        ];

        $type = fake()->randomElement(['page', 'group']);
        $privacy = fake()->randomElement(['public', 'closed', 'private']);
        $name = fake()->randomElement($names);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => fake()->sentence(12),
            'privacy' => $privacy,
            'is_verified' => fake()->boolean(20),
        ];
    }

    public function publicPage(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => 'public',
        ]);
    }

    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'group',
        ]);
    }
}
