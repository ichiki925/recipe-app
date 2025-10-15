<template>
    <div>
        <Head>
            <Title>Vanilla's Kitchen</Title>
            <Meta charset="UTF-8" />
            <Meta name="viewport" content="width=device-width, initial-scale=1.0" />
        </Head>

        <header>
            <div class="container">
            <div class="logo">
                <img src="/images/rabbit-shape.svg" alt="Logo" class="logo-image" />
                <span class="logo-text">Vanilla's Kitchen</span>
            </div>
            <nav>
                <ul>
                    <li><NuxtLink to="/auth/login"  class="nav-link">Login</NuxtLink></li>
                    <li><NuxtLink to="/auth/register" class="nav-link">Sign Up</NuxtLink></li>
                </ul>
            </nav>
            </div>
        </header>

        <main>
            <NuxtPage />
        </main>
    </div>
</template>

<style>
    @import "@/assets/css/common.css";
</style>
