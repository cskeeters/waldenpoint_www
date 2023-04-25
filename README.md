# Walden Point HOA Website

This is the source code for the website running at <http://www.waldenpoint.org>.

# Developer Notes

* This website is written in php and uses the [Slim Framework](https://www.slimframework.com/) (v4) and [twig](https://twig.symfony.com/), a template engine.

# Deploying

This website can be deployed on a linux computer with docker by running the following commands.

    cd /opt/
    git clone git@github.com:cskeeters/alpine_neovim.git
    cd alpine_neovim
    make
    cd ..

    git clone git@github.com:cskeeters/slim_app.git
    cd slim_app
    make
    cd ..

    git clone git@github.com:cskeeters/walden_point_hoa.git
    cd walden_point_hoa
    make build
    make

    # Download php modules required by the site
    make bash
    composer install

    # Generate beancount reports
    make bake


## Private Data

The members page is protected with a password and captcha.  These are read by the app from a text file in `app/.env`.  This file is not checked into this repository and can be created manually, or copied from the board's private files.  This file must have the following contents:

    RECAPTCHA_V3_SECRET_KEY=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    MEMBER_PASSCODE=XXXXXXXXXXXXXXXXXXX

The value for `RECAPTCHA_V3_SECRET_KEY` can be obtained by through the *v3 Admin Console* linked from <https://www.google.com/recaptcha/about/>.  This will **not** work if an Enterprise captcha key is generated.

The MEMBER_PASSCODE can be any thing you want.  When deploying publicly, the password needs to be give to the members.

## Member Information

The member data is stored in yaml format at app/lots.yaml. This file is also not checked in for privacy reasons and can be manually created or copied from the board's private files.  The format is as follows:

```yaml
%YAML 1.2
---
Lot 1:
- first: <Primary Owner First Name>
  last: <Primary Owner Last Name>
  email: <Primary Owner Email>
  tel: <Primary Owner telephone number>
- first: <Secondary Owner First Name>
  last: <Secondary Owner Last Name>
  email: <Secondary Owner Email>
  tel: <Secondary Owner telephone number>
Lot 2:
- first: John
  last: Doe
  email: john.doe@gmail.com
  tel: 555-555-5555
- first: Jane
  last: Doe
  email: jane.doe@gmail.com
  tel: 555-555-5555

(...)

...
```

NOTE: The file ends with 3 dots.

`email` and `tel` are optional.  Secondary owners are optional too.
