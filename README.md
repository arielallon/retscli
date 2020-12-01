# retscli
CLI for Phrets


## Sample config file
Create an mls-configs.yml file in the project root. (There's a gitignore to ensure it won't get committed.)

```yml
carets:
  # open tunnel: ssh -L 6103:rets172lax.raprets.com:6103 -N -4 -J jump centos@worker.box.ip -i .ssh/keys/deployer -vvv
  # add hosts: 127.0.0.1   rets172lax.raprets.com
  login_url: "http://rets172lax.raprets.com:6103/itech/itec/login.aspx"
  username: "YourUserName"
  password: "YourPassword"
  user_agent: "UserAgent/1.0"
  rets_version: "1.7.2"
  standard_names: true
  resources:
    listings:
      resource: "Property"
      classes:
        - "Residential"
        - "Mobile"
      queries_reference:
        -
          - ""
    media:
      resource: "Media"
      classes:
        - "PROP"
      queries_reference:
        -
          - ""
hudmls:
  login_url: "http://hudson.rets.paragonrels.com/rets/fnisrets.aspx/HUDSON/login"
  username: "AnotherUser"
  password: "WithAPassowrd"
  rets_version: "1.8"
  standard_names: false
  resources:
    listings:
      resource: "Property"
      classes:
        - "1F_1"
        - "CC_5"
      queries_reference:
        -
          - "(L_UpdateDate=2020-10-15T00:00:00-2020-11-15T00:00:00)"
      object:
        field: "Photo"
        by_location: true #untested
    media:
      resource: "Media"
      classes:
        - "1F_1"
        - "CC_5"
      queries_reference:
        -
          - "(MED_update_dt=2020-10-15T00:00:00-2020-11-15T00:00:00)"
globalmls:
  login_url: "http://globalmls-rets.paragonrels.com/rets/fnisrets.aspx/GLOBALMLS/login?rets-version=rets/1.7.2"
  username: "OneMoreUsername"
  password: "MySecretPassword"
  rets_version: "1.7.2"
  standard_names: false
  resources:
    listings:
      resource: "Property"
      classes:
        - "1F_1"
        - "2F_2"
      queries_reference:
        -
          - "(L_UpdateDate=2020-06-14T00:00:00-2020-07-14T01:00:00)"
      object:
        field: "Photo"
        by_location: true
mlspin:
  login_url: "https://mlspin-dd.apps.retsiq.com:443/contact/rets/login"
  username: "IGetit"
  password: "ReallyIDo"
  rets_version: "1.7.2"
  options:
    http_authentication_method: "basic"
  resources:
    listings:
      resource: "RESI"
      classes:
        - "SF"
        - "CC"
        - "MH"
      queries_reference:
        -
          - "(ModificationTimestamp=2020-07-08T10:06:00)"
          - "(ListingId=72615296)"
        -
          - "(ListingId=72615296,72648946,72648967,72648999,72716721,72638398,72702134,72711655,72681500,72552803,72689865,72707098,72689858,72718450,72682944,72715241,72715283,72684996,72684993,72717937,72705947)"
      object:
        field: "Photo"
        by_location: true
    offices:
      resource: "Office"
      classes:
        - "Office"
      queries_reference:
        -
          - "(ModificationTimestamp=1980-01-01-2010-01-01)"
        -
          - "(OfficeKey=0+)"
    members:
      resource: "Member"
      classes:
        - "Member"
      queries_reference:
        -
          - ""

```
