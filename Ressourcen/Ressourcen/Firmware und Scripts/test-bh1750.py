
ADDRESS = 0x00 # TODO: Change this

from bh1750 import BH1750
import smbus
def main():
    bus = smbus.SMBus(1)  # Use 1 for Raspberry Pi, 0 for older models
    sensor = BH1750(bus, ADDRESS)

    try:
        light_level = sensor.read_light()
        print(f"Light Level: {light_level} lux")
    except Exception as e:
        print(f"Error reading light level: {e}")

if __name__ == "__main__":
    main()
