# Install and use this script

  1. Copy the content of `src` into your shaarli instance (at its root with index.php)
  2. Copy `tumblr.ini.dev` to `tumblr.ini`
  3. Fill the `tumblr.ini`'s variables

```ini
tumblr = xxx.tumblr.com # your tumblr URL (can be another domain if you paid for it)
api_key = xxx # your tumblr's API key (see below)
private = true # if links should be private or public
shaarli_dir = # leave blank if you followed this tutorial, else, the path to shaarli
```
  4. Check if you got write right on the folder containing `import.php`
  5. Launch : via browser or via command line

### Tumblr API key

You need to register an app in tumblr :
  
  1. Log in your tumblr account
  2. Go to https://www.tumblr.com/oauth/apps
  3. Click `Register an application`
  4. Fill all mandatory fields + `website` (even if it's not marked as mandatory, trust me, it is)
  5. You got it ! **OAuth Consumer Key** !
  6. Tips : you can find it again in https://www.tumblr.com/settings/apps


### Limitations 

Tumblr's API can *only* retrieve 20 × 250 posts (I did the math, it's 5000)

This script does not resume import, so if you got more than 5k posts, it won't work.

