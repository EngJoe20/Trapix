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