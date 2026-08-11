<?php

namespace Database\Seeders;

use App\Models\MessageBox;
use Illuminate\Database\Seeder;

class MessageBoxSeeder extends Seeder
{
    public function run(): void
    {
        MessageBox::updateOrCreate(['name'=>'Welcome Modal Example'], [
            'title'=>'Welcome to Unclad Collection',
            'body_html'=>'<p>Discover licensed imagery, curated collections, and stories created for the naturist community.</p>',
            'presentation'=>'modal','trigger_type'=>'auto','page_patterns'=>['/'],'audience'=>'all','show_once'=>true,'is_dismissible'=>true,'is_active'=>true,'priority'=>10,
            'buttons'=>[
                ['label'=>'Browse Images','url'=>'/images','style'=>'primary'],
                ['label'=>'Read the Blog','url'=>'/blog','style'=>'outline'],
            ],
        ]);

        MessageBox::updateOrCreate(['name'=>'Bottom Banner Example'], [
            'title'=>'Stay connected',
            'body_html'=>'<p>This is the 200px message that rises from the bottom of the browser. It can carry a short announcement, image, action, or form.</p>',
            'presentation'=>'bottom_banner','trigger_type'=>'auto','page_patterns'=>['/'],'audience'=>'all','show_once'=>false,'is_dismissible'=>true,'is_active'=>true,'priority'=>20,
            'buttons'=>[['label'=>'Explore Collections','url'=>'/collections','style'=>'primary']],
        ]);

        MessageBox::updateOrCreate(['name'=>'Top Banner Action Example'], [
            'title'=>'Tell us what interests you',
            'body_html'=>'<p>This 200px message lowers from the top and is opened only when an element uses its trigger key.</p>',
            'presentation'=>'top_banner','trigger_type'=>'action','trigger_key'=>'example.interest-form','page_patterns'=>['/','/images*'],'audience'=>'all','show_once'=>false,'is_dismissible'=>true,'is_active'=>true,'priority'=>30,
            'form_fields'=>[
                ['name'=>'email','label'=>'Email','type'=>'email','required'=>true,'placeholder'=>'you@example.com','options'=>[]],
                ['name'=>'interest','label'=>'Main interest','type'=>'select','required'=>true,'placeholder'=>null,'options'=>['Photography','Editorial stories','Licensing','Community']],
            ],
            'form_submit_label'=>'Send Response','form_success_message'=>'Thanks — your response has been received.',
        ]);
    }
}
