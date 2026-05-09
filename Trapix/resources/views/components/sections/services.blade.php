<section id="services">

    <x-shared.container className="space-y-10 md:space-y-12">

        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-4">

            <x-shared.title>
                What we offer
            </x-shared.title>

            <x-shared.paragraph>
                Lorem ipsum dolor sit amet consectetur adipisicing elit.
            </x-shared.paragraph>

        </div>

        <!-- Services grid -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">

            @php
                $services = [
                    [
                        'title' => 'Social media marketing',
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Corrupti qui soluta cupiditate',
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>',
                    ],
                    [
                        'title' => 'Amazon affiliate marketing',
                        'description' => 'Sunt, ipsam, necessitatibus sint fugit officia laboriosam minima ab ullam at magni et. Quaerat, sint!',
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>',
                    ],
                    [
                        'title' => 'Email marketing',
                        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Corrupti qui soluta cupiditate',
                        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>',
                    ],
                ];
            @endphp

            @foreach ($services as $service)

                <x-cards.service
                    :title="$service['title']"
                    :description="$service['description']"
                    :icon="$service['icon']"
                />

            @endforeach

        </div>

    </x-shared.container>

</section>