plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

android {
    namespace = "iq.ghaniacademy.pay"
    compileSdk = 35
    defaultConfig {
        applicationId = "iq.ghaniacademy.pay"
        minSdk = 24
        targetSdk = 35
        versionCode = (System.getenv("BUILD_NUMBER") ?: "1").toInt()
        versionName = "1.0." + (System.getenv("BUILD_NUMBER") ?: "1")
    }
    signingConfigs {
        create("side") {
            storeFile = file("../../app/tool/debug.keystore")
            storePassword = "android"
            keyAlias = "androiddebugkey"
            keyPassword = "android"
        }
    }
    buildTypes {
        release {
            isMinifyEnabled = true
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"))
            signingConfig = signingConfigs.getByName("side")
        }
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions { jvmTarget = "17" }
}
