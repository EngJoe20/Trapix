<section>

    <x-shared.container className="space-y-8">

        <div class="text-center max-w-3xl mx-auto">

            <x-shared.title>
                Trusted by companies like
            </x-shared.title>

        </div>

        <div class="flex justify-center flex-wrap gap-4">

            @php
                $brands = [
                    [
                        'image' => 'logos/spotify.png',
                        'alt' => 'spotify'
                    ],
                    [
                        'image' => 'logos/slack.png',
                        'alt' => 'slack'
                    ],
                    [
                        'image' => 'logos/paypallogo.png',
                        'alt' => 'paypal'
                    ],
                    [
                        'image' => 'logos/spotify.png',
                        'alt' => 'spotify'
                    ],
                    [
                        'image' => 'logos/slack.png',
                        'alt' => 'slack'
                    ],
                    [
                        'image' => 'logos/paypallogo.png',
                        'alt' => 'paypal'
                    ],
                    [
                        'image' => 'logos/spotify.png',
                        'alt' => 'spotify'
                    ],
                    [
                        'image' => 'logos/slack.png',
                        'alt' => 'slack'
                    ],
                    [
                        'image' => 'logos/paypallogo.png',
                        'alt' => 'paypal'
                    ],
                    [
                        'image' => 'logos/spotify.png',
                        'alt' => 'spotify'
                    ],
                    [
                        'image' => 'logos/slack.png',
                        'alt' => 'slack'
                    ],
                    [
                        'image' => 'logos/paypallogo.png',
                        'alt' => 'paypal'
                    ],
                ];
            @endphp

            @foreach ($brands as $brand)

                <div class="p-4 sm:p-5 rounded-xl bg-body border border-box-border group">

                    <img
                        src="{{ asset($brand['image']) }}"
                        width="100"
                        height="60"
                        alt="{{ $brand['alt'] }}"
                        class="h-7 sm:h-10 w-auto ease-linear duration-300 grayscale group-hover:!grayscale-0 group-hover:scale-105"
                    >

                </div>

            @endforeach

        </div>

    </x-shared.container>

</section>