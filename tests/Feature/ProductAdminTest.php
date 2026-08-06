<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\Question;
use App\Models\User;
use App\Models\Wizard;
use Livewire\Livewire;

test('admin can list products', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Home Insurance']);

    $this->actingAs($user);

    Livewire::test(ListProducts::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$product]);
});

test('admin can create a product with required questions', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create();
    $question = Question::factory()->create([
        'wizard_id' => $wizard->id,
        'label' => 'Property address',
    ]);

    $this->actingAs($user);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Home Insurance',
            'wizard_id' => $wizard->id,
            'required_questions' => [$question->key],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $product = Product::query()->where('name', 'Home Insurance')->firstOrFail();

    expect($product->required_questions)->toBe([$question->key]);
});

test('admin can update a product\'s required questions', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create();
    $questionOne = Question::factory()->create(['wizard_id' => $wizard->id, 'label' => 'Q1']);
    $questionTwo = Question::factory()->create(['wizard_id' => $wizard->id, 'label' => 'Q2']);
    $product = Product::factory()->create(['wizard_id' => $wizard->id, 'required_questions' => [$questionOne->key]]);

    $this->actingAs($user);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['required_questions' => [$questionOne->key, $questionTwo->key]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->required_questions)->toBe([$questionOne->key, $questionTwo->key]);
});

test('a product cannot require a question from a different wizard', function () {
    $user = User::factory()->create();
    $wizardA = Wizard::factory()->create();
    $wizardB = Wizard::factory()->create();
    $questionFromOtherWizard = Question::factory()->create(['wizard_id' => $wizardB->id]);

    $this->actingAs($user);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Home Insurance',
            'wizard_id' => $wizardA->id,
            'required_questions' => [$questionFromOtherWizard->key],
        ])
        ->call('create')
        ->assertHasFormErrors(['required_questions']);

    expect(Product::query()->count())->toBe(0);
});

test('a product can require questions that belong to its own wizard', function () {
    $user = User::factory()->create();
    $wizardA = Wizard::factory()->create();
    $wizardB = Wizard::factory()->create();
    $questionA = Question::factory()->create(['wizard_id' => $wizardA->id, 'label' => 'Question A']);
    Question::factory()->create(['wizard_id' => $wizardB->id, 'label' => 'Question B']);

    $this->actingAs($user);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Scoped Product',
            'wizard_id' => $wizardA->id,
            'required_questions' => [$questionA->key],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('name', 'Scoped Product')->firstOrFail();

    expect($product->wizard_id)->toBe($wizardA->id)
        ->and($product->required_questions)->toBe([$questionA->key]);
});

test('a question key stays stable across rollback so product requirements keep working', function () {
    $user = User::factory()->create();
    $wizard = Wizard::factory()->create(['name' => 'Quote Form']);
    $question = Question::factory()->create([
        'wizard_id' => $wizard->id,
        'label' => 'Property address',
    ]);
    $originalKey = $question->key;

    $product = Product::factory()->create(['wizard_id' => $wizard->id, 'required_questions' => [$originalKey]]);

    $v1 = $wizard->createDraft();
    $v1->publish($user);

    $question->delete();
    $wizard->createDraft()->publish($user);
    $wizard->refresh();

    $wizard->rollbackTo($v1, $user);
    $wizard->refresh();

    $restoredQuestion = $wizard->questions()->where('label', 'Property address')->firstOrFail();

    expect($restoredQuestion->id)->not->toBe($question->id)
        ->and($restoredQuestion->key)->toBe($originalKey)
        ->and($product->fresh()->required_questions)->toBe([$originalKey]);
});
