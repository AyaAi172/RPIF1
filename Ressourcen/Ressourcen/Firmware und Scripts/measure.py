from optparse import OptionParser, OptionGroup # Command line
import sys # System
import RPi.GPIO as GPIO
from datetime import datetime # Timestamps
import time # Sleep
import requests # Send data to server
import tomllib # Read config file

def parse_args():
    usage = "usage: %prog [options]\n\nThis script reads the values of the connected sensors," \
    "prints them with the current timestamp to standard output and sends the data to a server.\n" \
    "Configuration is done via the 'config.toml' file.\n\n" \
    "Measurements are taken until the script is stopped."
    parser = OptionParser(usage)
    parser.add_option("-p", "--pretty",
                      action="store_true",
                      dest="pretty",
                      default=False,
                      help="Print the values followed by a unit. By default, values are printed without units, separated by commas.")
    parser.add_option("-q", "--quiet",
                      action="store_false",
                      dest="verbose",
                      default=True,
                      help="Do not print the measurements to standard output.")

    group = OptionGroup(parser, "Testing",
                        "These options are intended for testing purposes, in the case that either there is no server or there are no sensors.")
    group.add_option("--no-sensors",
                      action="store_false",
                      dest="sensors",
                      default=True,
                      help="No connection to the sensors is established and sensible, hardcoded values are returned.")
    group.add_option("-o", "--offline",
                      action="store_true",
                      dest="offline",
                      default=False,
                      help="No attempt at connecting to the server is made and no data is sent.")
    parser.add_option_group(group)
    
    (options, args) = parser.parse_args()

    return (options, args)

# I2C and sensor libraries
from smbus2 import SMBus
from bh1750 import BH1750
from bme280 import BME280
from mq135 import MQ135


# The main script
def main():
    # Parse the command line
    (options, args) = parse_args()

    GPIO.setwarnings(False)
    GPIO.setmode(GPIO.BCM)
    GPIO.setup(4, GPIO.OUT, initial=GPIO.LOW)

    # Load the configuration
    with open("/opt/station/config.toml", "rb") as f:
        cfg = tomllib.load(f)

    if options.sensors:
        for key in ['bme280', 'bh1750', 'mq135']:
            if not key in cfg['i2c'].keys():
                print(f"Address for sensor '{key}' is not configured, switching to 'no-sensor-mode'.", file=sys.stderr)
                options.sensors = False

        # Create the I2C bus
        try:
            bus = SMBus(1)
        except Exception:
            print("Failed to create I2C connection, switching to 'no-sensor-mode'.", file=sys.stderr)
            options.sensors = False
        # Create the sensors with the bus and their address
        try:
            bme280 = BME280(bus=bus, address=cfg['i2c']['bme280'])
        except Exception:
            print("Connection to BME280 failed, switching to 'no-sensor-mode'.", file=sys.stderr)
            options.sensors = False
        try:
            bh1750 = BH1750(bus=bus, address=cfg['i2c']['bh1750'])
        except Exception:
            print("Connection to BH1750 failed, switching to 'no-sensor-mode'.", file=sys.stderr)
            options.sensors = False
        try:
            mq135 = MQ135(bus=bus, address=cfg['i2c']['mq135'])
        except Exception:
            print("Connection to ADC failed, switching to 'no-sensor-mode'.", file=sys.stderr)
            options.sensors = False

    # Collect data until the script is stopped
    while True:
        try:
            # Read the next datum and store it in the corresponding array
            if (options.sensors):
                light = bh1750.read_light()
                temperature = bme280.read_temperature()
                pressure = bme280.read_pressure()
                humidity = bme280.read_humidity()
                gas = mq135.read_CO2()
            else:
                light = 0
                temperature = 20
                pressure = 1000
                humidity = 50
                gas = 500

            timestamp = datetime.now()

            # If the current light level is below the configured threshold, turn on the light
            if light < cfg['toggle_intensity']:
                GPIO.output(4, GPIO.HIGH)
            else:
                GPIO.output(4, GPIO.LOW)

            # Output data to standard output
            if options.verbose:
                if options.pretty:
                    print(f"{timestamp}: {light:05.2f} Lux {temperature:05.2f} C {pressure:05.2f} hPa {humidity:05.2f} % {gas:05.3f} ppm")
                else:
                    print(f"{timestamp}, {light:05.2f}, {temperature:05.2f}, {pressure:05.2f}, {humidity:05.2f}, {gas:05.3f}", flush=True)

            # Send data 
            if not options.offline:
                send(cfg['destination']['ip'], cfg['destination']['path'], cfg['station_serial'], timestamp, temperature, humidity, pressure, light, gas)
    
        except RuntimeError as error:
            continue
    
        # Wait the configured amount of time before the next reading
        time.sleep(cfg['interval'])

    GPIO.output(4, LOW)
    
# Function for sending the data to the server
def send(host, path, station_serial, time, temperature, humidity, pressure, light, gas):
    data = {
        'station_serial': station_serial,
        'timestamp': time,
        'temperature': temperature,
        'humidity': humidity,
        'pressure': pressure,
        'light': light,
        'gas': gas
    }
    try:
        # Send the data to the configured script on the configured host
        requests.post(f"http://{host}{path}", data, timeout=1)
    except Exception as e:
        print(f"Could not connect to server: {e}" , file=sys.stderr)


if __name__ == "__main__":
    main()
