<?php

use App\Models\MessageBox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns an eligible welcome message for its targeted page', function () {
    $box = MessageBox::create(['name'=>'Welcome','title'=>'Hello','presentation'=>'modal','trigger_type'=>'auto','page_patterns'=>['/'],'audience'=>'all','is_active'=>true]);
    $this->getJson('/message-boxes/eligible?path=/&trigger_type=auto', ['X-Message-Visitor'=>'11111111-1111-4111-8111-111111111111'])
        ->assertOk()->assertJsonPath('messages.0.id', $box->id);
    $this->getJson('/message-boxes/eligible?path=/blog&trigger_type=auto', ['X-Message-Visitor'=>'11111111-1111-4111-8111-111111111111'])
        ->assertOk()->assertJsonCount(0, 'messages');
});

it('honors show once for an authenticated user', function () {
    $user=User::factory()->create();
    $box=MessageBox::create(['name'=>'Once','presentation'=>'modal','trigger_type'=>'auto','page_patterns'=>['*'],'audience'=>'all','show_once'=>true,'is_active'=>true]);
    $this->actingAs($user)->postJson("/message-boxes/{$box->id}/seen")->assertOk();
    $this->actingAs($user)->getJson('/message-boxes/eligible?path=/&trigger_type=auto')->assertOk()->assertJsonCount(0,'messages');
});

it('returns an action message only for its trigger key', function () {
    $box=MessageBox::create(['name'=>'Action','presentation'=>'top_banner','trigger_type'=>'action','trigger_key'=>'asset.help','page_patterns'=>['/assets/*'],'audience'=>'all','is_active'=>true]);
    $this->getJson('/message-boxes/eligible?path=/assets/sample&trigger_type=action&trigger_key=asset.help', ['X-Message-Visitor'=>'22222222-2222-4222-8222-222222222222'])
        ->assertOk()->assertJsonPath('messages.0.id',$box->id);
});

it('stores configured form submissions', function () {
    $box=MessageBox::create(['name'=>'Form','presentation'=>'modal','trigger_type'=>'auto','page_patterns'=>['*'],'audience'=>'all','is_active'=>true,'form_fields'=>[['name'=>'email','label'=>'Email','type'=>'email','required'=>true,'options'=>[]]]]);
    $this->postJson("/message-boxes/{$box->id}/submit", ['email'=>'person@example.com'], ['X-Message-Visitor'=>'33333333-3333-4333-8333-333333333333'])->assertOk();
    $this->assertDatabaseHas('message_box_submissions',['message_box_id'=>$box->id]);
});
